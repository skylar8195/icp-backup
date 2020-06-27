<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\HeadingRowImport;
use App\Exports\HHProductsExport;
use App\Imports\HHProductsImport;
use App\Exports\HHOutletsExport;
use App\Imports\HHOutletsImport;
use App\Exports\HHMappingsExport;
use App\Imports\HHMappingsImport;
use App\Exports\HHSurveyExport;
use App\Imports\HHSurveyImport;
use App\Imports\HHMigrationImport;
use App\Household\Product;
use App\Household\ProductClassification;
use App\Household\Outlet;
use App\Household\Mapping;
use App\Household\Quotation;
use App\Household\UOM;
use Carbon\Carbon;
use App\Helper;
use App\Country;
use App\Location;
use App\Transaction;
use App\User;
use File;
use Excel;
use Auth;
use DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use Box\Spout\Writer\Style\StyleBuilder;

class HouseholdController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    #products
    public function dashboard()
    {
        $dbs = new \SQLite3('../database/production.sqlite');
        $dbs->loadExtension('libsqlitefunctions.dll');
        $user = Auth::User();
        $users = User::count();
        $locations = Location::where('ccode',Auth::User()->country)->count();
        $products_0 = Product::notAvailable()->count();
        $products_1 = Product::available()->count();
        $products_2 = 982-($products_0+$products_1);
        $outlets = Outlet::where('ccode',Auth::User()->country)->count();
        $outlets_mapped = Outlet::has('mappings')->count();
        $outlets_mapped_none = ($outlets-$outlets_mapped);
        $outlets_quotations = 0;


        $quotations = DB::table('household_quotations')->count();
        $qg15 = Product::has('quotations', '>', '14')->pluck('pcode');
        $quotations_g_15 = $qg15->count();
        $qg15 = $qg15->toArray();
        $qg15data = Quotation::whereIn('pcode', $qg15)->count();

        $ql15 = Product::where('avail',1)->orWhere('avail',2)->has('quotations', '>', '0')->pluck('pcode');
        $ql15 = $ql15->toArray();
        $ql15 = array_diff($ql15,$qg15);
        $quotations_l_15 = count($ql15);
        $ql15data = Quotation::whereIn('pcode', $ql15)->count();

        $results = $dbs->query("SELECT 
                                SUM(CASE WHEN (MMR < 0.3) THEN 1 ELSE 0 END) as mmr_total,
                                SUM(CASE WHEN (CV > 30) THEN 1 ELSE 0 END) as cv_total
                                FROM (
                                    SELECT
                                        pcode,
                                        (MIN(con_price)/MAX(con_price)) as MMR,
                                        (stdev(con_price)/AVG(con_price)*100) as CV
                                    FROM household_quotations GROUP BY pcode
                                ) Q
                             ");
        $qmmr = collect();
        while ($row = $results->fetchArray()) {
            $qmmr->add($row);
        }
        
        $quotations_mmr_filtered = $qmmr[0]['mmr_total'];
        $quotations_cv_filtered = $qmmr[0]['cv_total'];
        $transactions = Transaction::with('user')->orderBy('updated_at','DESC')->limit(10)->get();
        $page = 'dashboard';
        $country = Country::where('ccode',Auth::User()->country)->first();
        return view('household.dashboard.dashboard', compact('user','transactions','page','products_0','products_1','products_2','outlets','outlets_mapped','outlets_mapped_none','quotations','quotations_g_15','quotations_l_15','locations','users','quotations_cv_filtered','quotations_mmr_filtered','country','quotations_avail','quotations_notavail','qg15data','ql15data','qmmr'));
    }

    #products
    public function products()
    {
        $user = Auth::User();
        $classes = ProductClassification::get();
        $class1 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,5) as classids'))->get();
        $class2 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,6) as classids'),DB::raw('SUBSTR(classid,5,1) as ids'))->where('ids', '>', '0')->get();
        $class3 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,7) as classids'),DB::raw('SUBSTR(classid,6,1) as ids'))->where('ids', '>', '0')->get();
        $class4 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,8) as classids'),DB::raw('SUBSTR(classid,7,1) as ids'))->where('ids', '>', '0')->get();
        $page = 'products';
        return view('household.products.products', compact('user','classes','class1','class2','class3','class4','page'));
    }

    public function clQuery() {
        return ProductClassification::select('classid','classdesc',DB::raw('MIN(classid)'))->groupby('classids');
    }

    public function productsRaw() {
        $products = Product::select('pcode','pname','avail','minqty','maxqty','prefqty','uomid','survperiod','listgroup','pspecs','id')
                            ->with('uom:uomid,uomname')->get();
        return json_encode($products);
    }

    public function productsTable() {
        $products = Product::get();
        $country = Country::where('ccode',Auth::User()->country)->first();   
        return view('exports.products-hh', compact('products','country'));
    }

    public function downloadProductTable() {
        $country = Country::where('ccode',Auth::User()->country)->first();
        return Excel::download(new HHProductsExport($country,true), 'HHProducts_'.$country->ccode."_".date("d-m-Y").'.xlsx');
    }

    public function uploadProductsTable(Request $request) {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $filepath = $request->file('imported-file')->store('temp');
        $path = storage_path('app').'/'.$filepath; 
        

        $headings = (new HeadingRowImport)->toArray($path);
        if (in_array("icpproducttemplate", $headings[0][0])) {
            $import = new HHProductsImport($request->ccode);
            Excel::import($import, $path);
            if ($import->getErrors()) {
                $errors = $import->getErrors();
                return redirect()->back()->with(compact('errors'));
            }
            $success = $import->getRowCount();
            LOGG('UPDATE','uploaded HH products template with '.$import->getRowCount().' rows.');
        } else {
            $errors = array(array('row' => '-', 'error' => 'The uploaded file is invalid. Please check that you are uploading the correct <b>product template.</b>'));
            return redirect()->back()->with(compact('errors'));
        }
        
        return redirect()->back()->with(compact('success'));
    }

    public function updateAvailability(Request $request) {
        foreach ($request->data as $id) {
            $product = Product::where('pcode',$id)->first();
            $product->pcode = $id;
            $pavail = $product->avail;
            $product->avail = $request->availability;
            if ($request->availability == 0) {
                $product->survperiod = "";
            }
            $product->save();
            LOGG('UPDATE','updated HH product '.$id.' availability from '.$pavail.' to '.$request->availability.'.');
        }
        return Response()->json(array('success' => true));
    }

    public function updateFrequency(Request $request) {
        foreach ($request->data as $id) {
            $product = Product::where('pcode',$id)->first();
            $pfreq = $product->survperiod;
            $product->survperiod = $request->frequency;
            $product->save();
            LOGG('UPDATE','updated HH product frequency from '.$pfreq.' to '.$request->frequency.'.');
        }
        return Response()->json(array('success' => true));
    }



    #outlets
    public function outlets() {
        $user = Auth::User();
        $locations = Location::where('ccode',$user->country)->get();
        $country = Country::where('ccode',$user->country)->first();
        $page = 'outlets';
        return view('household.outlets.outlets', compact('user','locations','country','outlets','page'));
    }

    public function outletsRaw(Request $request) {
        // if ($request->option == "quotations") {
            $outlets = DB::table('household_outlets')
                            ->leftJoin('locations', function($join) {
                                $join->on('locations.loclvl2', '=', 'household_outlets.lvl2');
                                $join->on('locations.loclvl3', '=', 'household_outlets.lvl3');
                                $join->on('locations.loclvl4', '=', 'household_outlets.lvl4');
                                $join->on('locations.loclvl5', '=', 'household_outlets.lvl5');
                            })
                            ->select('household_outlets.id AS outlet_id','household_outlets.oname','household_outlets.ocode','lvl2','lvl3','lvl4','lvl5','otype','household_outlets.loctype','address','locname')
                            ->where('household_outlets.ccode',Auth::User()->country)
                            ->groupBy('household_outlets.id')
                            ->get();
        // } else if ($request->option == "mappings") {
        //     $outlets = DB::table('household_outlets')
        //                     ->leftJoin('locations', function($join){
        //                         $join->on('locations.loclvl2', '=', 'household_outlets.lvl2');
        //                         $join->on('locations.loclvl3', '=', 'household_outlets.lvl3');
        //                         $join->on('locations.loclvl4', '=', 'household_outlets.lvl4');
        //                         $join->on('locations.loclvl5', '=', 'household_outlets.lvl5');
        //                     })
        //                     ->select('household_outlets.id AS outlet_id','household_outlets.oname','household_outlets.ocode','lvl2','lvl3','lvl4','lvl5','otype','household_outlets.loctype','address','locname')
        //                     ->groupBy('household_outlets.id')
        //                     ->where('household_outlets.ccode',Auth::User()->country)
        //                     ->get();
        // }
        return $outlets->toArray();
    }

    public function createOutlet(Request $request) {
        $request->merge(['ocode' => force3digits($request->ocode)]);
        $user = Auth::User();
        $locations = Location::where('ccode',$user->country)->get();
        $country = Country::where('ccode',$user->country)->first();
        $messages = array(
            'ocode.unique' => 'The specified outlet code is already taken.'
        );
        $validator = Validator::make($request->toArray(), [
            'lvl2' => ['required'],
            'ocode' => ['required', 'string', 'max:3'],
            'oname' => ['required', 'string'],
            'address' => ['required', 'string'],
            'otype' => ['required', 'string'],
            'loctype' => ['required', 'string']
        ],$messages);
        if ($validator->fails()) {
            return response()->json(array('success' => false, 'error' => $validator->errors()->first()));
        } else {
            ($request->lvl3 == null) ? $request->lvl3 = "00" : $request->lvl3;
            ($request->lvl4 == null) ? $request->lvl4 = "00" : $request->lvl4;
            ($request->lvl5 == null) ? $request->lvl5 = "000" : $request->lvl5;

            $outlet_exists = Outlet::where('lvl2',$request->lvl2)
                                    ->where('lvl3',$request->lvl3)
                                    ->where('lvl4',$request->lvl4)
                                    ->where('lvl5',$request->lvl5)
                                    ->where('ocode',$request->ocode)->first();
            if ($outlet_exists) {
                return response()->json(array('success' => false, 'error' => 'The specified outlet code is already taken.'));
            } else {
                $outlet = new Outlet;
                $outlet->ocode = force3digits($request->ocode);
                $outlet->oname = $request->oname;
                $outlet->address = $request->address;
                $outlet->ccode = $user->country;
                $outlet->lvl2 = $request->lvl2;
                $outlet->lvl3 = $request->lvl3;
                $outlet->lvl4 = $request->lvl4;
                $outlet->lvl5 = $request->lvl5;
                $outlet->otype = $request->otype;
                $outlet->loctype = $request->loctype;
                $outlet->save();
            }
        }
        LOGG('CREATE','created outlet '.$request->outletname.'.');
        return response()->json(array('success' => true, 'message' => 'Successfully created outlet <b>'.$outlet->oname.'</b>.'));
        return redirect()->back()->withSuccess('Successfully created outlet <b>'.$outlet->oname.'</b>.');
    }

    public function outletsTable() {
        $products = Product::get();
        $country = Country::where('ccode',Auth::User()->country)->first();   
        $outlets = Outlet::where('ccode',Auth::User()->country)->get();   
        return view('exports.outlets-hh', compact('products','country','outlets'));
    }

    public function downloadOutletTable() {
        $country = Country::where('ccode',Auth::User()->country)->first();
        return Excel::download(new HHOutletsExport($country,true), 'HHOutlets_'.$country->ccode."_".date("d-m-Y").'.xlsx');
    }

    public function uploadOutletsTable(Request $request) {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $filepath = $request->file('imported-file')->store('temp');
        $path = storage_path('app').'/'.$filepath; 
        
        $headings = (new HeadingRowImport)->toArray($path);
        if (in_array("icpoutlettemplate", $headings[0][0])) {
            if ($request->upload_option == "erase") {
                Outlet::truncate();
                Mapping::truncate();
                Quotation::truncate();
            }
            $import = new HHOutletsImport($request->upload_option);
            Excel::import($import, $path);
            if ($import->getErrors()) {
                $errors = $import->getErrors();
                return redirect()->back()->with(compact('errors'));
                // return Response()->json(array('success' => false, 'errors' => );
            }
            $success = $import->getRowCount();
            $success = 'Successfully uploaded template containing <b>'.$import->getRowCount().'</b> records.';
            LOGG('UPDATE','uploaded HH outlets template with '.$import->getRowCount().' records.');
            return redirect()->back()->with(compact('success'));
        } else {
            $errors = array(array('row' => '-', 'error' => 'The uploaded file is invalid. Please check that you are uploading the correct <b>outlet template.</b>'));
            return redirect()->back()->with(compact('errors'));
        }

        
    }

    public function editOutlet(Request $request) {
        $outlet = Outlet::where('id',$request->oid)->first();
        if ($outlet) {
            ($request->lvl3 == null) ? $request->lvl3 = "00" : $request->lvl3;
            ($request->lvl4 == null) ? $request->lvl4 = "00" : $request->lvl4;
            ($request->lvl5 == null) ? $request->lvl5 = "000" : $request->lvl5;

            $outlet_exists = Outlet::where('lvl2',$request->lvl2)
                                    ->where('lvl3',$request->lvl3)
                                    ->where('lvl4',$request->lvl4)
                                    ->where('lvl5',$request->lvl5)
                                    ->where('ocode',force3digits($request->ocode))->first();

            if ($outlet_exists && ($outlet_exists != $outlet)) {
                return response()->json(array('success' => false, 'error' => 'The specified outlet code is already taken.'));
            } else {
                $outlet->lvl2 = $request->lvl2;
                $outlet->lvl3 = $request->lvl3;
                $outlet->lvl4 = $request->lvl4;
                $outlet->lvl5 = $request->lvl5;
                $outlet->ocode = force3digits($request->ocode);
                $outlet->oname = $request->oname;
                $outlet->address = $request->address;
                $outlet->loctype = $request->loctype;
                $outlet->otype = $request->otype;
                $outlet->save();

                LOGG('UPDATE','edited outlet with outlet code: '.$request->ocode.'.');
                return response()->json(array('success' => true, 'message' => 'Outlet <b>'.$outlet->oname.'</b> edited successfully!'));
            }
        } else {
            return response()->json(array('success' => false, 'error' => 'An error occured, outlet could not be found.'));
        }
    }

    public function deleteOutlet(Request $request) {
        $outlet = Outlet::where('ccode',$request->ccode)->where('id',$request->ocode)->first();
        if ($outlet) {
            $outlet->delete();
            LOGG('DELETE','deleted outlet with outlet code: '.$request->ocode.'.');
            return redirect()->back()->withSuccess('Outlet '.$outlet->oname.' deleted successfully!');
        } else {
            return redirect()->back()->withError('Outlet could not be found.');
        }
    }

    public function locationLevelRaw(Request $request) {
        $user = Auth::User();
        $locations = Location::where('ccode',$user->country)
                            ->where('loclvl',(int)$request->loclvl+1)
                            ->where('loclvl2',$request->loclvl2)
                            ->when($request->loclvl3, function ($query) use ($request) {
                                return $query->where('loclvl3', $request->loclvl3);
                            })
                            ->when($request->loclvl4, function ($query) use ($request) {
                                return $query->where('loclvl4', $request->loclvl4);
                            })
                            ->when($request->loclvl5, function ($query) use ($request) {
                                return $query->where('loclvl5', $request->loclvl5);
                            })->get();
        return Response()->json(array('success' => true, 'locations' => $locations));
    }

    public function getMappedProducts(Request $request) {
        if ($request->freq) {
            $products = DB::table('household_mappings')
                        ->leftJoin('household_outlets','household_mappings.oid','=','household_outlets.id')
                        ->leftJoin('household_products','household_products.pcode','=','household_mappings.pcode')
                        ->where('household_outlets.ccode',Auth::User()->country)
                        ->where('survperiod',$request->freq)
                        ->where('household_outlets.id',$request->oid)->get();
        } else {    
            $products = DB::table('household_mappings')
                        ->leftJoin('household_outlets','household_mappings.oid','=','household_outlets.id')
                        ->leftJoin('household_products','household_products.pcode','=','household_mappings.pcode')
                        ->where('household_outlets.ccode',Auth::User()->country)
                        ->where('household_outlets.id',$request->oid)->get();
                        // dd($products);
        }
        $filtered_products = $products->map(function ($p) {
            return [
                'pcode'         => $p->pcode,
                'pname'         => $p->pname,
                'prefqty'       => $p->prefqty,
                'minqty'        => $p->minqty,
                'maxqty'        => $p->maxqty,
                'listgroup'     => $p->listgroup,
                'uomid'         => UOM::where('uomid',$p->uomid)->first()->uomname,
                'survperiod'    => $p->survperiod,
                'ocode'         => $p->ocode,
                'oname'         => $p->oname,
                'quotes'        => DB::table('household_quotations')
                                    ->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
                                    ->where('pcode',$p->pcode)->where('household_quotations.oid',$p->oid)->where('ccode',Auth::User()->country)->count(),
                'oid'           => $p->oid
            ];
        });
        return $filtered_products;
    }

    public function getUnmappedProducts(Request $request) {
        $prodarray = array();
        $allproducts = Product::select('avail','pcode','pname')->where(function ($query) {
                        $query->where('avail', 1)->orWhere('avail', 2);
                    })->where('survperiod', '!=', '')->get();

        $mappedproducts = DB::table('household_mappings')
                            ->leftJoin('household_outlets','household_outlets.id','=','household_mappings.oid')
                            ->leftJoin('household_products','household_products.pcode','=','household_mappings.pcode')
                            ->where('household_outlets.id',$request->oid)
                            ->where('household_outlets.ccode',Auth::User()->country)->get();

        $filtered_products = $allproducts->map(function ($p) use ($mappedproducts, $prodarray) {
            if ($mappedproducts->contains('pcode', $p->pcode)) {
                return false;    
            } else {
                return [
                    'pcode'    => $p->pcode,
                    'pname'    => $p->pname,
                ];
            }
        })->reject(function ($value) {
            return $value === false;
        });

        foreach ($filtered_products as $p) {
            array_push($prodarray, 
                    array('pcode' => $p['pcode'],'pname' => $p['pname'])
            );
        }

        return json_encode($prodarray);
    }

    #mapping
    public function mapping() {
        $user = Auth::User();
        $locations = Location::where('ccode',$user->country)->get();
        $country = Country::where('ccode',$user->country)->first();
        $classes = ProductClassification::get();
        $class1 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,5) as classids'))->get();
        $class2 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,6) as classids'),DB::raw('SUBSTR(classid,5,1) as ids'))->where('ids', '>', '0')->get();
        $class3 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,7) as classids'),DB::raw('SUBSTR(classid,6,1) as ids'))->where('ids', '>', '0')->get();
        $class4 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,8) as classids'),DB::raw('SUBSTR(classid,7,1) as ids'))->where('ids', '>', '0')->get();
        $page = 'mapping';
        return view('household.mapping.mapping', compact('user','locations','country','outlets','classes','class1','class2','class3','class4','page'));
    }

    public function mapProducts(Request $request) {
        $outlet = Outlet::where('id',$request->oid)->where('ccode',Auth::User()->country)->first();
        $count = 0;
        foreach ($request->products as $pcode) {

            $mapping = Mapping::updateOrCreate([
                'oid'        => $outlet->id,
                'ocode'      => force3digits($outlet->ocode),
                'pcode'      => $pcode
            ]);

            $count++;   
        }
        LOGG('UPDATE','mapped product(s) to outlet '.$outlet->oname.'.');
        return Response()->json(array('success' => true, 'count' => $count, 'outlet' => $outlet->oname));
    }

    public function unmapProducts(Request $request) {
        $outlet = Outlet::where('id',$request->oid)->where('ccode',Auth::User()->country)->first();
        $count = 0;
        foreach ($request->products as $pcode) {
            $mapping = Mapping::where('pcode',$pcode)->where('oid',$outlet->id)->first();
            if ($mapping) {
                $mapping->delete();
            }
            $count++;
        }
        LOGG('UPDATE','un-mapped products from outlet '.$outlet->oname.'.');
        return Response()->json(array('success' => true, 'count' => $count, 'outlet' => $outlet->oname));
    }

    public function exportMappings() {
        $mappings = Mapping::with('product')->with('outlet')->get();
        $country = Country::where('ccode',Auth::User()->country)->first();
        $outlets = Outlet::where('ccode',Auth::User()->country)->get();
        return view('exports.mappings-hh', compact('mappings','country','outlets'));
    }

    public function downloadMappingTable() {
        $country = Country::where('ccode',Auth::User()->country)->first();
        return Excel::download(new HHMappingsExport($country,true), 'HHMappings_'.$country->ccode."_".date("d-m-Y").'.xlsx');
    }

    public function uploadMappingTable(Request $request) {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $filepath = $request->file('imported-file')->store('temp');
        $path = storage_path('app').'/'.$filepath; 

        $headings = (new HeadingRowImport)->toArray($path);
        if (in_array("icpmappingtemplate", $headings[0][0])) {
            try {
                Mapping::truncate();
                $import = new HHMappingsImport;
                Excel::import($import, $path);

                if ($import->getErrors()) {
                    $failures = $import->getErrors();
                    return redirect()->back()->with(compact('failures'));
                }

            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                 $failures = $e->failures();
                 return redirect()->back()->with(compact('failures'));
            }
            $success = $import->getRowCount();
            LOGG('UPDATE','uploaded HH mapping template with '.$import->getRowCount().' records.');
            return redirect()->back()->with(compact('success'));
        } else {
            $failures = array(array('row' => '-', 'error' => 'The uploaded file is invalid. Please check that you are uploading the correct <b>mapping template.</b>'));
            return redirect()->back()->with(compact('failures'));
        }
   
    }


    #surveygen
    public function surveyGeneration() {
        $user = Auth::User();
        $locations = Location::where('ccode',$user->country)->get();
        $country = Country::where('ccode',$user->country)->first();
        $page = 'surveygen';
        return view('household.surveygen.surveygen', compact('user','locations','country','page'));
    }

    public function exportSurveyGeneration() {
        $mappings = Mapping::with('product')->with('outlet')->get();
        $country = Country::where('ccode',Auth::User()->country)->first();
        $outlets = Outlet::where('ccode',Auth::User()->country)->get();
        return view('exports.surveys-hh', compact('mappings','country','outlets'));
    }

    public function downloadSurveyGeneration(Request $request) {
        $selected_array = explode(",", $request->selected_array);
        $country = Country::where('ccode',Auth::User()->country)->first();
        if ($request->collector != null) {
            $collector = str_replace(' ', '', $request->collector);
            return Excel::download(
                new HHSurveyExport($country,$request->collector,$request->freq,$request->year,$request->month,$selected_array),'HHQuestionnaire_'.$collector.'_'.$country->ccode."_".$request->year.$request->freq.$request->month."_".date("d-m-Y").'.xlsx'
            );
        } else {
            return Excel::download(
                new HHSurveyExport($country,$request->collector,$request->freq,$request->year,$request->month,$selected_array),'HHQuestionnaire_'.$country->ccode."_".$request->year.$request->freq.$request->month."_".date("d-m-Y").'.xlsx'
            );
        }
    }

    public function uploadSurvey(Request $request) {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $filepath = $request->file('imported-file')->store('temp');
        $path = storage_path('app').'/'.$filepath; 

        $headings = (new HeadingRowImport)->toArray($path);
        if (in_array("icpsurveytemplate", $headings[0][0])) {
            $import = new HHSurveyImport($request->ccode);
            Excel::import($import, $path);
            if ($import->getErrors()) {
                $errors = $import->getErrors();
                return redirect()->back()->with(compact('errors'));
            }
            $success = $import->getRowCount();
            LOGG('UPDATE','uploaded HH survey template with '.$import->getRowCount().' rows.');
            return redirect()->back()->with(compact('success'));
        } else {
            $errors = array(array('row' => '-', 'error' => 'The uploaded file is invalid. Please check that you are uploading the correct <b>survey template.</b>'));
            return redirect()->back()->with(compact('errors'));
        }
    }

    #dataentry
    public function dataEntry() {
        $user = Auth::User();
        $locations = Location::where('ccode',$user->country)->get();
        $country = Country::where('ccode',$user->country)->first();
        $outlets = Outlet::where('ccode',$user->country)->get();
        $classes = ProductClassification::get();
        $class1 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,5) as classids'))->get();
        $class2 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,6) as classids'),DB::raw('SUBSTR(classid,5,1) as ids'))->where('ids', '>', '0')->get();
        $class3 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,7) as classids'),DB::raw('SUBSTR(classid,6,1) as ids'))->where('ids', '>', '0')->get();
        $class4 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,8) as classids'),DB::raw('SUBSTR(classid,7,1) as ids'))->where('ids', '>', '0')->get();
        $page = 'dataentry';
        return view('household.dataentry.dataentry', compact('user','locations','country','outlets','classes','class1','class2','class3','class4','page'));
    }

    public function createQuotation(Request $request) {
        if ($request->iswkb === "yes") {
            $validator = Validator::make($request->toArray(), [
                'obv_qty' => ['required','numeric'],
                'price' => ['required','numeric'],
                'con_price' => ['required'],
                'price_type' => ['required'],
                'obv_date' => ['required', 'date'],
                'brand' => ['required']
            ]);    
        } else {
            $validator = Validator::make($request->toArray(), [
                'obv_qty' => ['required','numeric'],
                'price' => ['required','numeric'],
                'con_price' => ['required'],
                'price_type' => ['required'],
                'obv_date' => ['required', 'date'],
            ]);
        }
        
        if ($validator->fails()) {
            return Response()->json(array('success' => false, 'error' => $validator->errors()->first()));
        } else {
            $exists = Quotation::where('pcode',$request->pcode)
                                ->where('oid',$request->oid)
                                ->where('obv_qty',$request->obv_qty)
                                ->where('price',$request->price)
                                ->where('con_price',$request->con_price)
                                ->where('price_type',$request->price_type)
                                ->where('obv_date',$request->obv_date)
                                ->first();
            if ($exists) {
                return Response()->json(array('success' => false, 'error' => 'Duplicate record exists'));
            } else {
                $quotation = new Quotation;
                $quotation->pcode = $request->pcode;
                $quotation->obv_qty = $request->obv_qty;
                $quotation->price = $request->price;
                $quotation->con_price = $request->con_price;
                $quotation->price_type = $request->price_type;
                $quotation->obv_date = $request->obv_date;
                $quotation->brand = $request->brand;
                $quotation->remarks = $request->remarks;
                
                $quotation->ref_period = $request->ref_period;
                $quotation->ref_year = $request->ref_year;
                $quotation->ref_months = $request->ref_months;
                $quotation->encoder = Auth::User()->id;
                $quotation->oid = $request->oid;
                $quotation->save();
            }  
        }
        LOGG('CREATE','created quotation with [pcode:'.$quotation->pcode.',oid:'.$request->ocode.',price:'.$quotation->price.',date:'.$quotation->obv_date.'].');
        return Response()->json(array('success' => true, 'quotation' => $quotation));
    }

    public function editQuotation(Request $request) {
        $validator = Validator::make($request->toArray(), [
            'qid' => ['required','numeric'],
            'oid' => ['required','numeric'],
            'obv_qty' => ['required','numeric'],
            'price' => ['required'],
            'con_price' => ['required'],
            'price_type' => ['required'],
            'obv_date' => ['required', 'date']
        ]);
        if ($validator->fails()) {
            return Response()->json(array('success' => false, 'error' => $validator->errors()->first()));
        } else {
            $quotation = Quotation::where('id',$request->qid)->first();
            $qpcode = $quotation->pcode;
            $qprice = $quotation->price;
            $qobv_date = $quotation->obv_date;
            $quotation->pcode = $request->pcode;
            $quotation->obv_qty = $request->obv_qty;
            $quotation->price = $request->price;
            $quotation->con_price = $request->con_price;
            $quotation->price_type = $request->price_type;
            $quotation->obv_date = $request->obv_date;
            $quotation->brand = $request->brand;
            $quotation->remarks = $request->remarks;
            $quotation->save();
        }
        LOGG('UPDATE','updated quotation '.$quotation->id.'.');
        LOGG('UPDATE','updated quotation from [pcode:'.$qpcode.',price:'.$qprice.',date:'.$qobv_date.'] to [pcode:'.$quotation->pcode.',price:'.$quotation->price.',date:'.$quotation->obv_date.']');
        return Response()->json(array('success' => true, 'quotation' => $quotation));
    }

    public function deleteQuotation(Request $request) {
        $validator = Validator::make($request->toArray(), [
            'qid' => ['required'],
        ]);
        if ($validator->fails()) {
            return Response()->json(array('success' => false, 'error' => $validator->errors()->first()));
        } else {
            $quotation = Quotation::where('id',$request->qid);
            $quotation->delete();
        }
        LOGG('DELETE','deleted quotation with ID# '.$request->qid.'.');
        return Response()->json(array('success' => true, 'quotation' => $quotation));
    }

    public function getOutletProductQuotations(Request $request) {
        if ($request->oid) {
            $quotations = DB::table('household_quotations')
                            ->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
                            ->leftJoin('users','users.id','=','household_quotations.encoder')
                            ->where('household_outlets.id',$request->oid)->where('pcode',$request->pcode)
                            ->where('household_outlets.ccode',Auth::User()->country)
                            ->select(
                                'household_quotations.id as qid','*'
                            )
                            ->get();
        } else {
            $quotations = DB::table('household_quotations')
                            ->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
                            ->leftJoin('users','users.id','=','household_quotations.encoder')
                            ->where('household_quotations.pcode',$request->pcode)
                            ->select(
                                'household_quotations.id as qid','*'
                            )
                            ->get();
                            // dd($request->all());
        }
        return $quotations;
    }

    public function downloadQuotations() {
        $country = Country::where('ccode',Auth::User()->country)->select('cname','ccode')->first();
        $info = collect([
            ['ICP APSS Version 3.0' => 'Raw Quotation Template'],
            ['Country: '.$country->cname.'('.$country->ccode.')'],
            ['Downloaded on: '.date("y-m-d h:i:s")],
            ['Instructions: This is an optimized template designed to accommodate an increased number of rows. Fill up the columns in the sheet named QuotationData and use the codes listed below when necessary. Cell validation will be done once uploaded to the system.'],
            ['Note on Converted Price: Converted price is auto computed upon upload. When downloaded, it is only displayed for reference. When uploading, it may be left blank or unchanged'],
            [''],
            ['PRICE TYPE'],
            ['R - Regular'],
            ['B - Bargain'],
            ['D - Discount'],
            [''],
            ['REFERENCE PERIOD'],
            ['<YEAR><FREQUENCY>'],
            ['ex: 2020M01'],
            [''],
            ['FREQUENCY'],
            ['M01 = Monthly - January'],
            ['M02 = Monthly - February'],
            ['M03 = Monthly - March'],
            ['M04 = Monthly - April'],
            ['M05 = Monthly - May'],
            ['M06 = Monthly - June'],
            ['M07 = Monthly - July'],
            ['M08 = Monthly - August'],
            ['M09 = Monthly - September'],
            ['M10 = Monthly - October'],
            ['M11 = Monthly - November'],
            ['M12 = Monthly - December'],
            ['Q01 = Quarterly - January to March'],
            ['Q02 = Quarterly - April to June'],
            ['Q03 = Quarterly - July to September'],
            ['Q04 = Quarterly - October to December'],
            ['S01 = Semi-Annualy - January to June'],
            ['S02 = Semi-Annualy - July to December'],
            ['A01 = Annual - January to December'],
        ]);
        
        $sheets = new SheetCollection([
            'Reference' => $info,
            'QuotationData' => $this->quotationsGenerator()
        ]);

        $style = (new StyleBuilder())
        ->setFontBold()
        ->setBackgroundColor('D8D8D8')
        ->build();

        return (new FastExcel($sheets))->headerStyle($style)->download('HHQuotations_'.Auth::User()->country.'_'.date("d-m-Y").'.xlsx');
    }

    public function quotationsGenerator() {
        foreach (Quotation::with('outlet:id,oname,lvl2,lvl3,lvl4,lvl5,ocode')->limit(10)->cursor() as $data) {
                $flat = [
                    'oid'              => $data->outlet()->first()->id,
                    'LocLevel2'        => $data->outlet()->first()->lvl2,
                    'LocLevel3'        => $data->outlet()->first()->lvl3,
                    'LocLevel4'        => $data->outlet()->first()->lvl4,
                    'LocLevel5'        => $data->outlet()->first()->lvl5,
                    'OutletCode'       => $data->outlet()->first()->ocode,
                    'ProductCode'      => $data->pcode,
                    'ReferencePeriod'  => $data->ref_year.$data->ref_period.($data->ref_months),
                    'ObservedDate'     => $data->obv_date,
                    'ObservedQuantity' => $data->obv_qty,
                    'ObservedPrice'    => $data->price,
                    'ConvertedPrice'   => $data->con_price,
                    'PriceType'        => $data->price_type,
                    'Brand'            => $data->brand,
                    'Remarks'          => $data->remarks
                ];
                yield $flat;
        }
    }

    public function uploadQuotations(Request $request) {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $filepath = $request->file('imported-file')->store('temp');
        $path = storage_path('app').'/'.$filepath; 
        $quotations = (new FastExcel)->sheet(2)->import($path, function ($data) {
            $outlet = Outlet::where('lvl2',$data['LocLevel2'])
                            ->where('lvl3',$data['LocLevel3'])
                            ->where('lvl4',$data['LocLevel4'])
                            ->where('lvl5',$data['LocLevel5'])
                            ->where('ocode',$data['OutletCode'])
                            ->first();
            $product = Product::where('pcode',$data['ProductCode'])->first();
            $haserror = $this->validateQuotations($outlet,$product,$data);
            if ($haserror == false) {
                return Quotation::updateOrCreate(
                    [
                        'oid'          => $outlet->id,
                        'pcode'        => $data['ProductCode']."",
                        'ref_period'   => substr($data['ReferencePeriod'],4,1),
                        'ref_year'     => substr($data['ReferencePeriod'],0,4),
                        'ref_months'   => substr($data['ReferencePeriod'],5,2),
                        'brand'        => $data['Brand'],
                    ],
                    [
                        'obv_date'      => $data['ObservedDate'],
                        'obv_qty'       => $data['ObservedQuantity'],
                        'price'         => $data['ObservedPrice'],
                        'con_price'     => (floatval($data['ObservedPrice'])/floatval($data['ObservedQuantity']))*floatval($product->prefqty),
                        'price_type'    => $data['PriceType'],
                        'remarks'       => $data['Remarks'],
                        'encoder'       => Auth::User()->id
                    ]
                );
            }
        });
        if ($this->getQuotationErrors()) {
            $errors = $this->getQuotationErrors();
            return redirect()->back()->with(compact('errors'));
        } else {
            $success = $this->getQuotationRowCount();
            LOGG('UPDATE','uploaded HH quotation raw template with '.$this->getQuotationRowCount().' rows.');
            return redirect()->back()->with(compact('success'));
        }
    }

    public $count = 2;
    public $errorarray = [];
    public function validateQuotations($outlet,$product,$data) {
        $count = $this->count;
        $haserror = false;
        $error =  "";   
        $month    = substr($data['ObservedDate'],0,2);
        $day      = substr($data['ObservedDate'],3,2);
        $year     = substr($data['ObservedDate'],6,4);
        $price_type = $data['PriceType'];

        if ($outlet == "" || $outlet == null) {
            $error = $error.'Specified outlet-location codes do not exist: <b>'.$data['LocLevel2'].$data['LocLevel3'].$data['LocLevel4'].$data['LocLevel5'].$data['OutletCode'].'</b><br>';
            $haserror = true;
        }
        if ($product == "" || $product == null) {
            $error = $error.'Specified product does not exist: <b>'.$data['ProductCode'].'</b><br>';
            $haserror = true;
        }
        if (substr($data['ReferencePeriod'],0,4) != "2020" && substr($data['ReferencePeriod'],0,4) != "2021") {
            $error = $error.'Specified year exceeds allowable years of 2020 and 2021: <b>'.substr($data['ReferencePeriod'],0,4).'</b><br>';
            $haserror = true;  
        }
        if (substr($data['ReferencePeriod'],4,1) != "M" && substr($data['ReferencePeriod'],4,1) != "Q" && substr($data['ReferencePeriod'],4,1) != "S" && substr($data['ReferencePeriod'],4,1) != "A") {
            $error = $error.'Specified ref period should be M, Q, S, or A. Found: <b>'.substr($data['ReferencePeriod'],4,1).'</b><br>';
            $haserror = true;  
        }
        if (substr($data['ReferencePeriod'],5,2) < 1 || substr($data['ReferencePeriod'],5,2) > 12) {
            $error = $error.'Specified ref months should be a number from 1 to 12. Found: <b>'.substr($data['ReferencePeriod'],5,2).'</b><br>';
            $haserror = true;  
        }
        if ($day > 31 || $day < 1) {
            $error = $error.'Day must be a number from 1-31. Found: <b>'.$day.'</b><br>';
            $haserror = true;   
        }
        if ($month > 12 || $month < 1) {
            $error = $error.'Month must be a number from 1-12. Found: <b>'.$month.'</b><br>';
            $haserror = true;   
        }
        if ($year > 2021 || $year < 2020) {
            $error = $error.'Years accepted are 2020 to 2021. Found: <b>'.$year.'</b>';
            $haserror = true;   
        }
        if (!is_numeric($data['ObservedQuantity'])) {
            $error = $error.'Quantity must be a number. Found '.$data['ObservedQuantity'].'<br>';
            $haserror = true;
        }
        $minqty = $product->minqty;
        $maxqty = $product->maxqty;
        if (($data['ObservedQuantity'] > $maxqty) || ($data['ObservedQuantity'] < $minqty)) {
            $error = $error.'Quantity must fall within range <b>'.$minqty.'-'.$maxqty.'</b>. Found '.$data['ObservedQuantity'].'<br>';
            $haserror = true;
        }
        if (!is_numeric($data['ObservedPrice'])) {
            $error = $error.'Price must be a number. Found: <b>'.$data['ObservedPrice'].'</b><br>';
            $haserror = true;   
        }
        if ($price_type != "R" && $price_type != 'B' && $price_type != 'D' && $price_type != "r" && $price_type != 'b' && $price_type != 'd') {
            $error = $error.'Price type must be <b>R</b> (regular), <b>B</b> (bargain), or <b>D</b> (discounted). Found: <b>'.$price_type.'</b><br>';
            $haserror = true;
        }

        if ($haserror) {
          $error = [
              'row' => $count,
              'error' => $error
          ];
          array_push($this->errorarray,$error);
        }
        ++$this->count;
        return $haserror;
    }

    public function getQuotationRowCount(): int
    {
        return ($this->count)-1;
    }
    
    public function getQuotationErrors()
    {
        if (count($this->errorarray)>0) {
          return $this->errorarray;
        } else {
          return false;
        }   
    }

    public function getOutletQuotationsSummary(Request $request) {

        $dbs = new \SQLite3('../database/production.sqlite');
        $dbs->loadExtension('libsqlitefunctions.dll');
        if ($request->oid) {


            $stmt = $dbs->prepare('SELECT
            COUNT(q.id), MIN(con_price), MAX(con_price), AVG(con_price), stdev(con_price) as std, MIN(con_price)/MAX(con_price) as mmr, stdev(con_price)/AVG(con_price)*100 as cv
                                            from household_quotations q
                                            left join household_products p on p.pcode = q.pcode
                                            left join household_outlets o on o.id = q.oid
                                            where p.pcode=:pcode and o.id=:oid
                                            group by p.pcode limit 1');
            $stmt->bindValue(':oid',$request->oid);
        } else {
            $stmt = $dbs->prepare('SELECT
            COUNT(q.id), MIN(con_price), MAX(con_price), AVG(con_price), stdev(con_price) as std, MIN(con_price)/MAX(con_price) as mmr, stdev(con_price)/AVG(con_price)*100 as cv
                                            from household_quotations q
                                            left join household_products p on p.pcode = q.pcode
                                            left join household_outlets o on o.id = q.oid
                                            where p.pcode=:pcode
                                            group by p.pcode limit 1');
        }
        $stmt->bindValue(':pcode',$request->pcode);
        $results = $stmt->execute();

        $qsummary = collect();
        while ($row = $results->fetchArray()) {
            $qsummary->add($row);
        }
        if (count($qsummary)>0) {
            $qsummary = $qsummary[0];
            return Response()->json(array('success' => true, 'qsummary' => $qsummary, 'mmr' => number_format($qsummary[5],2), 'std' => $qsummary[4], 'cv' => $qsummary[6]));
        } else {
            return Response()->json(array('success' => true, 'qsummary' => $qsummary, 'mmr' => '', 'std' => '', 'cv' => ''));
        }
    }


    #data validation
    public function dataValidation()
    {
        $user = Auth::User();
        $products = Product::all();
        $classes = ProductClassification::get();
        $class1 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,5) as classids'))->get();
        $class2 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,6) as classids'),DB::raw('SUBSTR(classid,5,1) as ids'))->where('ids', '>', '0')->get();
        $class3 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,7) as classids'),DB::raw('SUBSTR(classid,6,1) as ids'))->where('ids', '>', '0')->get();
        $class4 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,8) as classids'),DB::raw('SUBSTR(classid,7,1) as ids'))->where('ids', '>', '0')->get();
        $page = 'datavalidation';
        return view('household.dataval.dataval', compact('user','products','classes','class1','class2','class3','class4','page'));
    }

    public function dataValidationRawData() {
        $dbs = new \SQLite3('../database/production.sqlite');
        $dbs->loadExtension('libsqlitefunctions.dll');
        $stmt = $dbs->prepare('SELECT
                                    p.pcode,
                                    pname,
                                    avg(con_price) as avgprice,
                                    count(q.id) as quotations,
                                    min(con_price) as min,
                                    max(con_price) as max,
                                    min(con_price)/max(con_price) as mmr,
                                    stdev(con_price)/avg(con_price)*100 as cv,
                                    oname,
                                    prefqty,
                                    minqty,
                                    maxqty,
                                    p.uomid,
                                    survperiod,
                                    listgroup,
                                    u.uomname
                                from household_quotations q
                                left join household_products p on p.pcode = q.pcode
                                left join household_outlets o on o.id = q.oid
                                left join household_uom u on u.uomid = p.uomid
                                where o.ccode = :ccode
                                group by p.pcode');
        $stmt->bindValue(':ccode',Auth::User()->country);
        $results = $stmt->execute();
        $filtered_quotations = collect();
        while ($row = $results->fetchArray()) {
            $filtered_quotations->add($row);
        }
        return json_encode($filtered_quotations);
    }
    
    #data validation
    public function reports()
    {
        $user = Auth::User();
        $products = Product::all();
        $classes = ProductClassification::get();
        $class1 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,5) as classids'))->get();
        $class2 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,6) as classids'),DB::raw('SUBSTR(classid,5,1) as ids'))->where('ids', '>', '0')->get();
        $class3 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,7) as classids'),DB::raw('SUBSTR(classid,6,1) as ids'))->where('ids', '>', '0')->get();
        $class4 = $this->clQuery()->addSelect(DB::raw('SUBSTR(classid,0,8) as classids'),DB::raw('SUBSTR(classid,7,1) as ids'))->where('ids', '>', '0')->get();
        $page = 'reports';

        $error_count = "";

        $dbs = new \SQLite3('../database/production.sqlite');
        $dbs->loadExtension('libsqlitefunctions.dll');
        $stmt = $dbs->prepare('SELECT 
                                p.pcode, p.pname, avg(con_price), count(q.id), min(con_price), max(con_price), min(con_price)/max(con_price), stdev(con_price)/avg(con_price)*100 
                                from household_quotations q
                                left join household_products p on p.pcode = q.pcode
                                where p.avail = 1 or p.avail = 2
                                group by p.pcode');
        $stmt->bindValue(':ccode',Auth::User()->country);
        $results = $stmt->execute();
        $quotations = collect();
        while ($row = $results->fetchArray()) {
            $quotations->add($row);
        }
        $err = 0;
        $error_count = $quotations->map(function ($q) use (&$err) {
            if (($q['stdev(con_price)/avg(con_price)*100'])>30 || $q['min(con_price)/max(con_price)']<0.30 || $q[3]<15) {
                $err++;
                return [
                    'pcode'         => $q['pcode'],
                    // 'pname'         => $q['pname'],
                    // 'avgprice'      => (float)$q['avg(con_price)'],
                    // 'quotations'    => (float)$q[3],
                    // 'cv'            => (float)$q['stdev(con_price)/avg(con_price)*100'],
                    // 'min'           => (float)$q['min(con_price)'],
                    // 'max'           => (float)$q['max(con_price)'],
                    // 'mmr'           => (float)$q['min(con_price)/max(con_price)']
                ];  
            }
        });
        $error_count = $err;
        return view('household.reports.reports', compact('user','products','classes','class1','class2','class3','class4','page','error_count'));
    }

    #datamigration
    public function dataMigration()
    {
        $user = Auth::User();
        $page = 'datamigration';
        return view('household.datamigration', compact('user','page'));
    }

    #datamigration upload
    public function uploadDataMigration(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $filepath = $request->file('imported-file')->store('temp');
        $path = storage_path('app').'/'.$filepath; 
        // DB::table('household_products')->where('pcode','>','0')->update(['avail' => 0, 'survperiod' => '']);
        // Location::where('ccode',Auth::User()->country)->delete();
        // Outlet::where('ccode',Auth::User()->country)->delete();
        // Mapping::truncate();
        $import = new HHMigrationImport();
        // $import->onlySheets('Product','Outlets','OPMapping1');
        Excel::import($import, $path);

        LOGG('UPDATE','migrated database file.');
        return redirect()->back()->withSuccess('success');
    }

    #download backup
    public function databaseBackup() {
        return response()->download(database_path('production.sqlite'),'database_'.Auth::User()->country.'_'.date("d-m-Y").'.sqlite');
    }

    #upload backup
    public function databaseImport(Request $request) {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $file = $request->file('file');
        $abc = $file->move(base_path('database'), 'production.sqlite');
        return redirect()->back()->withSuccess('success');
    }

}
