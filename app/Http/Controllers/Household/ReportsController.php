<?php

namespace App\Http\Controllers\Household;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Exports\Reports\HHReportOne;
use App\Exports\Reports\HHReportTwo;
use App\Exports\Reports\HHReportThree;
use App\Exports\Reports\HHReportFive;
use App\Exports\Reports\HHReportSix;
use App\Exports\Reports\HHReportSeven;
use App\Exports\Reports\HHReportEight;
use App\Exports\Reports\HHReportNine;
use App\Household\Product;
use App\Household\ProductClassification;
use App\Household\Outlet;
use App\Household\Mapping;
use App\Household\Quotation;
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

class ReportsController extends Controller
{

     public function reportSelector(Request $request) {
    		switch ($request->report) {
    		    	case "1":
    		        return $this->reportOne($request->report_action);
    		        break;
    		    	case "2":
    		        return $this->reportTwo($request->report_action);
    		        break;
    		     case "3":
    		        return $this->reportThree($request->report_action,$request->start_date,$request->end_date);
    		        break;
    		     case "5":
    		        return $this->reportFive($request->report_action,$request->start_date,$request->end_date);
    		        break;
    		     case "6":
    		        return $this->reportSix($request->report_action,$request->start_date,$request->end_date);
    		        break;
    		     case "7":
    		        return $this->reportSeven($request->report_action);
    		        break;
                 case "9":
                    return $this->reportNine($request->report_action,$request->start_date,$request->end_date);
                    break;
    		}
     }

    public function reportOne($action = "") {
    		$country 	   = Country::where('ccode',Auth::User()->country)->first();
    		$prod_count        = Product::all()->count();
    		$prod_count_na     = Product::where('avail',0)->count();
    		$prod_count_av     = Product::where('avail',1)->count();
    		$prod_count_avl    = Product::where('avail',2)->count();
    		$prodclasses = ProductClassification::all();

    		$prodclasses = $prodclasses->map(function ($q) {
			  $classid_trim = rtrim($q->classid, "0");
			  if (strlen($classid_trim)==3) {
			  	$classid_trim = $classid_trim."0";
			  }
			  $prod = Product::where('pcode','like', $classid_trim.'%')->count();
			  $prod_na = Product::where('avail',0)->where('pcode','like', $classid_trim.'%')->count();
			  $prod_av = Product::where('avail',1)->where('pcode','like', $classid_trim.'%')->count();
			  $prod_avl = Product::where('avail',2)->where('pcode','like', $classid_trim.'%')->count();
			  return [
			      'classid'   		=> $classid_trim,
			      'classdesc' 		=> $q->classdesc,
			      'total_count' 	=> $prod,
			      'total_count_na' 	=> $prod_na,
			      'total_count_av' 	=> $prod_av,
			      'total_count_avl' 	=> $prod_avl
			  ];
		});

    		if ($action == "download") {
    			// $TOKEN = "downloadToken";
    			// // Sets a cookie so that when the download begins the browser can
    			// // unblock the submit button (thus helping to prevent multiple clicks).
    			// // The false parameter allows the cookie to be exposed to JavaScript.
    			// $this->setCookieToken( $TOKEN, $_GET[ $TOKEN ], false );
    			return Excel::download(new HHReportOne($country), 'HHReport1_'.$country->ccode."_".date("d-m-yy").'.xlsx');
    		} else {
    			if ($action == "view") {
    				$back = '<a href="javascript:history.back()">< Back</a>';
    			} else {
    				$back = '';
    			}
    			return view('household.reports.one', compact('prod_count','prod_count_na','prod_count_av','prod_count_avl','prodclasses','country','back'));
    		}
    }

   	public function reportTwo($action = "") {
   		$country 	   = Country::where('ccode',Auth::User()->country)->first();
   		$locations   = Location::where('ccode',$country->ccode)->get();

   		$countrylevel = "";
   		if ($country->loclvl3 == "") {
   			$countrylevel = 2;
   		} else if ($country->loclvl4 == "") {
   			$countrylevel = 3;
   		} else if ($country->loclvl5 == "") {
   			$countrylevel = 4;
   		} else {
            $countrylevel = 5;
        }

    		$locations_outlets = $locations->map(function ($q) use ($countrylevel) {
            if ($q->loclvl < $countrylevel) {
                if ($q->loclvl == 2) {
                    $outlets = Outlet::where('lvl2',$q->loclvl2)->count();
                    $out_t1 = Outlet::where('lvl2',$q->loclvl2)->where('otype',1)->count();
                    $out_t2 = Outlet::where('lvl2',$q->loclvl2)->where('otype',2)->count();
                    $out_t3 = Outlet::where('lvl2',$q->loclvl2)->where('otype',3)->count();
                    $out_t4 = Outlet::where('lvl2',$q->loclvl2)->where('otype',4)->count();
                    $out_t5 = Outlet::where('lvl2',$q->loclvl2)->where('otype',5)->count();
                    $out_t6 = Outlet::where('lvl2',$q->loclvl2)->where('otype',6)->count();
                    $out_t7 = Outlet::where('lvl2',$q->loclvl2)->where('otype',7)->count();
                    $out_t8 = Outlet::where('lvl2',$q->loclvl2)->where('otype',8)->count();
                    $out_t9 = Outlet::where('lvl2',$q->loclvl2)->where('otype',9)->count();
                    $ol1  = Outlet::where('lvl2',$q->loclvl2)->where('loctype',1)->count();
                    $ol2  = Outlet::where('lvl2',$q->loclvl2)->where('loctype',2)->count();
                } else if ($q->loclvl == 3) {
                    $outlets = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->count();
                    $out_t1 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',1)->count();
                    $out_t2 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',2)->count();
                    $out_t3 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',3)->count();
                    $out_t4 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',4)->count();
                    $out_t5 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',5)->count();
                    $out_t6 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',6)->count();
                    $out_t7 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',7)->count();
                    $out_t8 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',8)->count();
                    $out_t9 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',9)->count();
                    $ol1  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('loctype',1)->count();
                    $ol2  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('loctype',2)->count();
                } else if ($q->loclvl == 4) {
                    $outlets = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->count();
                    $out_t1 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',1)->count();
                    $out_t2 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',2)->count();
                    $out_t3 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',3)->count();
                    $out_t4 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',4)->count();
                    $out_t5 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',5)->count();
                    $out_t6 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',6)->count();
                    $out_t7 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',7)->count();
                    $out_t8 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',8)->count();
                    $out_t9 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',9)->count();
                    $ol1  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('loctype',1)->count();
                    $ol2  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('loctype',2)->count();
                }
            } else {
$outlets = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->count();
$out_t1 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',1)->count();
$out_t2 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',2)->count();
$out_t3 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',3)->count();
$out_t4 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',4)->count();
$out_t5 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',5)->count();
$out_t6 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',6)->count();
$out_t7 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',7)->count();
$out_t8 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',8)->count();
$out_t9 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',9)->count();
$ol1  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('loctype',1)->count();
$ol2  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('loctype',2)->count();
            }
            return [
                 'loc_code'         => $q->loclvl2.$q->loclvl3.$q->loclvl4.$q->loclvl5,
                 'loc_name'         => $q->locname,
                 'total_outlets'    => $outlets,
                 'outlets_t1'       => $out_t1,
                 'outlets_t2'       => $out_t2,
                 'outlets_t3'       => $out_t3,
                 'outlets_t4'       => $out_t4,
                 'outlets_t5'       => $out_t5,
                 'outlets_t6'       => $out_t6,
                 'outlets_t7'       => $out_t7,
                 'outlets_t8'       => $out_t8,
                 'outlets_t9'       => $out_t9,
                 'loctype1'     => $ol1,
                 'loctype2'     => $ol2
            ];
        });
   		if ($action == "download") {
   			return Excel::download(new HHReportTwo($country), 'HHReport2_'.$country->ccode."_".date("d-m-yy").'.xlsx');
   		} else {
   			if ($action == "view") {
    				$back = '<a href="javascript:history.back()">< Back</a>';
    			} else {
    				$back = '';
    			}
   			return view('household.reports.two', compact('country','back','locations_outlets'));
   		}
 	}


 	public function reportThree($action,$start_date,$end_date) {
    		$country 	   = Country::where('ccode',Auth::User()->country)->first();
    		$locations   = Location::where('ccode',Auth::User()->country)->where('loclvl',2)->get();
    		$products    = Product::orderBy('pcode','asc')->select('pcode','pname')->get();

    		if ($start_date != null) {
	    		$quotations_filtered = $products->map(function ($q) use ($locations, $start_date, $end_date) {
	    			$dataarray = [];
	    			$country_totals = DB::table('household_quotations')
								->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
								->select(
									DB::raw('count(household_quotations.id) as total'),
									DB::raw('sum(case when household_outlets.loctype=1 then 1 else NULL end) as rural'),
									DB::raw('sum(case when household_outlets.loctype=2 then 1 else NULL end) as urban')
								)
								->where('pcode',$q->pcode)
								->where('ccode',Auth::User()->country)
								->whereBetween('obv_date',array($start_date, $end_date))
								->first();
				array_push($dataarray, [$country_totals->total,$country_totals->rural,$country_totals->urban]);
	    			foreach ($locations as $location) {
					$values = DB::table('household_quotations')
								->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
								->select(
									DB::raw('count(household_quotations.id) as total'),
									DB::raw('sum(case when household_outlets.loctype=1 then 1 else NULL end) as rural'),
									DB::raw('sum(case when household_outlets.loctype=2 then 1 else NULL end) as urban')
								)
								->where('pcode',$q->pcode)
								->where('household_outlets.lvl2',$location->loclvl2)
								->whereBetween('obv_date',array($start_date, $end_date))
								->where('ccode',Auth::User()->country)
								->first();
					array_push($dataarray, [$values->total,$values->rural,$values->urban]);
	    			}
				return [
					'pcode' => $q->pcode,
					'pname' => $q->pname,
					'data' => $dataarray,
				];

			});
	    	} else {
                $quotations_totaled = [];
                $values = DB::table('household_quotations')
                            ->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
                            ->select(
                                DB::raw('count(household_quotations.id) as total'),
                                DB::raw('sum(case when household_outlets.loctype=1 then 1 else NULL end) as rural'),
                                DB::raw('sum(case when household_outlets.loctype=2 then 1 else NULL end) as urban')
                            )
                            ->where('ccode',Auth::User()->country)
                            ->first();
                array_push($quotations_totaled, [$values->total,$values->rural,$values->urban]);
                foreach ($locations as $location) {
                    $values = DB::table('household_quotations')
                                ->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
                                ->select(
                                    DB::raw('count(household_quotations.id) as total'),
                                    DB::raw('sum(case when household_outlets.loctype=1 then 1 else NULL end) as rural'),
                                    DB::raw('sum(case when household_outlets.loctype=2 then 1 else NULL end) as urban')
                                )
                                ->where('household_outlets.lvl2',$location->loclvl2)
                                ->where('ccode',Auth::User()->country)
                                ->first();
                    array_push($quotations_totaled, [$values->total,$values->rural,$values->urban]);
                }


	    		$quotations_filtered = $products->map(function ($q) use ($locations) {
	    			$dataarray = [];
	    			$country_totals = DB::table('household_quotations')
								->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
								->select(
									DB::raw('count(household_quotations.id) as total'),
									DB::raw('sum(case when household_outlets.loctype=1 then 1 else NULL end) as rural'),
									DB::raw('sum(case when household_outlets.loctype=2 then 1 else NULL end) as urban')
								)
								->where('pcode',$q->pcode)
								->where('ccode',Auth::User()->country)
								->first();
				array_push($dataarray, [$country_totals->total,$country_totals->rural,$country_totals->urban]);
	    			foreach ($locations as $location) {
					$values = DB::table('household_quotations')
								->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
								->select(
									DB::raw('count(household_quotations.id) as total'),
									DB::raw('sum(case when household_outlets.loctype=1 then 1 else NULL end) as rural'),
									DB::raw('sum(case when household_outlets.loctype=2 then 1 else NULL end) as urban')
								)
								->where('pcode',$q->pcode)
								->where('household_outlets.lvl2',$location->loclvl2)
								->where('ccode',Auth::User()->country)
								->first();
					array_push($dataarray, [$values->total,$values->rural,$values->urban]);
	    			}
				return [
					'pcode' => $q->pcode,
					'pname' => $q->pname,
					'data' => $dataarray,
				];

			});
	    	}

    		if ($action == "download") {
    			return Excel::download(new HHReportThree($country,$quotations_filtered,$quotations_totaled), 'HHReport3_'.$country->ccode."_".date("d-m-yy").'.xlsx');
    		} else {
    			$back = '<a href="javascript:history.back()">< Back</a>';
    			return view('household.reports.three', compact('locations','country','products','quotations_filtered','back'));
    		}
    }


   	public function reportFive($action,$start_date,$end_date) {
   		$country 	   = Country::where('ccode',Auth::User()->country)->first();
    		$products    = Product::orderBy('pcode','asc')->get();    		

   		if ($action == "download") {
   			return Excel::download(new HHReportFive($country,$start_date,$end_date), 'HHReport5_'.$country->ccode."_".date("d-m-yy").'.xlsx');
   		} else {
   			$back = '<a href="javascript:history.back()">< Back</a>';
   			return view('household.reports.five', compact('country','back','quotations_filtered'));
   		}
 	}

 	public function reportSix($action,$start_date,$end_date) {

   		$country 	   = Country::where('ccode',Auth::User()->country)->first();
		$products    = Product::select('pcode','pname','avail')->orderBy('pcode','asc')->get();

		$dbs = new \SQLite3('../database/production.sqlite');
    	$dbs->loadExtension('libsqlitefunctions.dll');
		

if ($start_date != null) {
    $stmt = $dbs->prepare('SELECT
    						p.pcode, p.pname, p.avail, p.survperiod,
    						avg(con_price) 						as nat_avgprice, 
    						count(q.id) 						as nat_quotations, 
    						min(con_price) 						as nat_min, 
    						max(con_price) 						as nat_max, 
    						min(con_price)/max(con_price) 		as nat_mmr, 
    						stdev(con_price)/avg(con_price)*100 as nat_cv,

IFNULL((sum(case when o.loctype=1 then con_price else 0 end))/(sum(case when o.loctype=1 then 1 else 0 end)),NULL) as rur_avgprice,
sum(case when o.loctype=1 then 1 else NULL end) as rur_quotations,
min(case when o.loctype=1 then q.con_price else NULL end) as rur_min,
max(case when o.loctype=1 then q.con_price else NULL end) as rur_max,
min(case when o.loctype=1 then q.con_price else NULL end)/max(case when o.loctype=1 then q.con_price else NULL end) as rur_mmr,
(stdev(case when o.loctype=1 then q.con_price else NULL end)/IFNULL((sum(case when o.loctype=1 then con_price else 0 end))/(sum(case when o.loctype=1 then 1 else 0 end)),"0"))*100 as rur_cv,

IFNULL((sum(case when o.loctype=2 then con_price else 0 end))/(sum(case when o.loctype=2 then 1 else 0 end)),NULL) as urb_avgprice,
sum(case when o.loctype=2 then 1 else NULL end) as urb_quotations,
min(case when o.loctype=2 then q.con_price else NULL end) as urb_min,
max(case when o.loctype=2 then q.con_price else NULL end) as urb_max,
min(case when o.loctype=2 then q.con_price else NULL end)/max(case when o.loctype=2 then q.con_price else NULL end) as urb_mmr,
(stdev(case when o.loctype=2 then q.con_price else NULL end)/IFNULL((sum(case when o.loctype=2 then con_price else 0 end))/(sum(case when o.loctype=2 then 1 else 0 end)),"0"))*100 as urb_cv,

IFNULL((sum(case when l.capcity=1 then con_price else 0 end))/(sum(case when l.capcity=1 then 1 else 0 end)),NULL) as cap_avgprice,
sum(case when l.capcity=1 then 1 else NULL end) as cap_quotations,
min(case when l.capcity=1 then q.con_price else NULL end) as cap_min,
max(case when l.capcity=1 then q.con_price else NULL end) as cap_max,
min(case when l.capcity=1 then q.con_price else NULL end)/max(case when l.capcity=1 then q.con_price else NULL end) as cap_mmr,
(stdev(case when l.capcity=1 then q.con_price else NULL end)/IFNULL((sum(case when l.capcity=1 then con_price else 0 end))/(sum(case when l.capcity=1 then 1 else 0 end)),"0"))*100 as cap_cv

    						FROM household_products p
                            left outer join household_quotations q on p.pcode = q.pcode
                            left outer join household_outlets o on o.id = q.oid
                            left outer join locations l on l.loclvl2=o.lvl2 and l.loclvl3=o.lvl3 and l.loclvl4=o.lvl4 and l.loclvl5=o.lvl5
                            where (q.obv_date BETWEEN :startdate and :enddate)
                            group by p.pcode');
    $stmt->bindValue(':startdate',$start_date);
    $stmt->bindValue(':enddate',$end_date);
    $results = $stmt->execute();
} else {
	$stmt = $dbs->prepare('SELECT
    						p.pcode, p.pname, p.avail, p.survperiod,
    						avg(con_price) 						as nat_avgprice, 
    						count(q.id) 						as nat_quotations, 
    						min(con_price) 						as nat_min, 
    						max(con_price) 						as nat_max, 
    						min(con_price)/max(con_price) 		as nat_mmr, 
    						stdev(con_price)/avg(con_price)*100 as nat_cv,

IFNULL((sum(case when o.loctype=1 then con_price else 0 end))/(sum(case when o.loctype=1 then 1 else 0 end)),NULL) as rur_avgprice,
sum(case when o.loctype=1 then 1 else NULL end) as rur_quotations,
min(case when o.loctype=1 then q.con_price else NULL end) as rur_min,
max(case when o.loctype=1 then q.con_price else NULL end) as rur_max,
min(case when o.loctype=1 then q.con_price else NULL end)/max(case when o.loctype=1 then q.con_price else NULL end) as rur_mmr,
(stdev(case when o.loctype=1 then q.con_price else NULL end)/IFNULL((sum(case when o.loctype=1 then con_price else 0 end))/(sum(case when o.loctype=1 then 1 else 0 end)),"0"))*100 as rur_cv,

IFNULL((sum(case when o.loctype=2 then con_price else 0 end))/(sum(case when o.loctype=2 then 1 else 0 end)),NULL) as urb_avgprice,
sum(case when o.loctype=2 then 1 else NULL end) as urb_quotations,
min(case when o.loctype=2 then q.con_price else NULL end) as urb_min,
max(case when o.loctype=2 then q.con_price else NULL end) as urb_max,
min(case when o.loctype=2 then q.con_price else NULL end)/max(case when o.loctype=2 then q.con_price else NULL end) as urb_mmr,
(stdev(case when o.loctype=2 then q.con_price else NULL end)/IFNULL((sum(case when o.loctype=2 then con_price else 0 end))/(sum(case when o.loctype=2 then 1 else 0 end)),"0"))*100 as urb_cv,

IFNULL((sum(case when l.capcity=1 then con_price else 0 end))/(sum(case when l.capcity=1 then 1 else 0 end)),NULL) as cap_avgprice,
sum(case when l.capcity=1 then 1 else NULL end) as cap_quotations,
min(case when l.capcity=1 then q.con_price else NULL end) as cap_min,
max(case when l.capcity=1 then q.con_price else NULL end) as cap_max,
min(case when l.capcity=1 then q.con_price else NULL end)/max(case when l.capcity=1 then q.con_price else NULL end) as cap_mmr,
(stdev(case when l.capcity=1 then q.con_price else NULL end)/IFNULL((sum(case when l.capcity=1 then con_price else 0 end))/(sum(case when l.capcity=1 then 1 else 0 end)),"0"))*100 as cap_cv

    						FROM household_products p
                            left outer join household_quotations q on p.pcode = q.pcode
                            left outer join household_outlets o on o.id = q.oid
                            left outer join locations l on l.loclvl2=o.lvl2 and l.loclvl3=o.lvl3 and l.loclvl4=o.lvl4 and l.loclvl5=o.lvl5
                            group by p.pcode');
	$stmt->bindValue(':ccode',Auth::User()->country);
	$results = $stmt->execute();
}

$filtered_data = collect();
while ($row = $results->fetchArray()) {
    $filtered_data->add($row);
}

   		if ($action == "download") {
   			return Excel::download(new HHReportSix($country,$filtered_data,$products,$start_date,$end_date), 'HHR6_'.$country->ccode."_".date("d-m-yy").'.xlsx');
   		} else {
	    		//do nothing - no quick view
   		}
 	}

 	public function reportSeven($action) {
   		$country 	   = Country::where('ccode',Auth::User()->country)->first();

        $dbs = new \SQLite3('../database/production.sqlite');
        $dbs->loadExtension('libsqlitefunctions.dll');
        $results = $dbs->query('SELECT
                            pcode,
                            pname,
                            avg,
                            quotes,
                            min,
                            max,
                            stdev,
                            cv,
                            mmr,
                            (avg-stdev) as lowerlimit,
                            (avg+stdev) as upperlimit
                          FROM (
                              SELECT
                                  household_quotations.pcode,
                                  pname,
                                  AVG(con_price) as avg,
                                  COUNT(id) as quotes,
                                  MIN(con_price) as min,
                                  MAX(con_price) as max,
                                  stdev(con_price) as stdev,
                                  (stdev(con_price)/AVG(con_price)*100) as cv,
                                  MIN(con_price)/MAX(con_price) as mmr
                              FROM household_quotations LEFT JOIN household_products ON household_products.pcode = household_quotations.pcode GROUP BY household_quotations.pcode ORDER BY household_quotations.pcode ASC
                          ) Q WHERE cv>30 OR mmr<0.3');
        $quotations = collect();
        while ($row = $results->fetchArray()) {
            $quotations->add($row);
        }

   		if ($action == "download") {
   			return Excel::download(new HHReportEight($country,$quotations), 'HHReport7_'.$country->ccode."_".date("d-m-yy").'.xlsx');
   		} else {
   			$back = '<a href="javascript:history.back()">< Back</a>';
   			return view('household.reports.seven', compact('country','back','filtered_quotations'));
   		}
 	}

    public function reportNine($action,$start_date,$end_date) {
                    $country       = Country::where('ccode',Auth::User()->country)->first();
                    $locations   = Location::where('ccode',Auth::User()->country)->where('loclvl',2)->get();
                    $products    = Product::orderBy('pcode','asc')->select('pcode','pname')->get();
                    
                    if ($start_date != null) {
                        DB::statement("SELECT load_extension('".base_path()."/database/libsqlitefunctions');");
                        $quotes = $products->map(function ($q) use ($locations) {
                            $dataarray = [];
                            $values = DB::table('household_quotations')
                                        ->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
                                        ->select(
                                            DB::raw('avg(con_price) as avg'),
                                            DB::raw('count(con_price) as quotes'),
                                            DB::raw('(stdev(con_price)/AVG(con_price)*100) as cv'),
                                            DB::raw('min(con_price) as min'),
                                            DB::raw('max(con_price) as max'),
                                            DB::raw('min(con_price)/max(con_price) as mmr')
                                        )
                                        ->where('pcode',$q->pcode)
                                        ->where('ccode',Auth::User()->country)
                                        ->whereBetween('obv_date',array($start_date, $end_date))
                                        ->first();
                            array_push($dataarray, [$values->avg, $values->quotes, $values->cv, $values->min, $values->max, $values->mmr]);
                            foreach ($locations as $location) {
                                    $values = DB::table('household_quotations')
                                        ->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
                                        ->select(
                                            DB::raw('avg(con_price) as avg'),
                                            DB::raw('count(con_price) as quotes'),
                                            DB::raw('(stdev(con_price)/AVG(con_price)*100) as cv'),
                                            DB::raw('min(con_price) as min'),
                                            DB::raw('max(con_price) as max'),
                                            DB::raw('min(con_price)/max(con_price) as mmr')
                                        )
                                        ->where('pcode',$q->pcode)
                                        ->where('household_outlets.lvl2',$location->loclvl2)
                                        ->whereBetween('obv_date',array($start_date, $end_date))
                                        ->where('ccode',Auth::User()->country)
                                        ->first();
                                    array_push($dataarray, [$values->avg, $values->quotes, $values->cv, $values->min, $values->max, $values->mmr]);
                            }
                            return [
                                'pcode' => $q->pcode,'pname' => $q->pname,'data' => $dataarray,
                            ];
                        });
                    } else {
                        $quotes = $products->map(function ($q) use ($locations) {
                            $dataarray = [];
                            $dbs = new \SQLite3('../database/production.sqlite');
                            $dbs->loadExtension('libsqlitefunctions.dll');
                            $stmt = $dbs->prepare("SELECT
                                                    avg(con_price) as avg,
                                                    count(con_price) as quotes,
                                                    (stdev(con_price)/AVG(con_price)*100) as cv,
                                                    min(con_price) as min,
                                                    max(con_price) as max,
                                                    min(con_price)/max(con_price) as mmr
                                                  FROM household_quotations
                                                  WHERE pcode = :pcode LIMIT 1");
                            $stmt->bindValue(':pcode',$q->pcode);
                            $results = $stmt->execute();
                            $values = collect();
                            while ($row = $results->fetchArray()) {
                                $values->add($row);
                            }
                            array_push($dataarray, [$values[0]['avg'], $values[0]['quotes'], $values[0]['cv'], $values[0]['min'], $values[0]['max'], $values[0]['mmr']]);
                            foreach ($locations as $location) {
                                    $stmt = $dbs->prepare("SELECT
                                                            avg(con_price) as avg,
                                                            count(con_price) as quotes,
                                                            (stdev(con_price)/AVG(con_price)*100) as cv,
                                                            min(con_price) as min,
                                                            max(con_price) as max,
                                                            min(con_price)/max(con_price) as mmr
                                                          FROM household_quotations
                                                          LEFT JOIN household_outlets ON household_outlets.id = household_quotations.oid
                                                          WHERE pcode = :pcode AND lvl2 = :lvl2 LIMIT 1");
                                    $stmt->bindValue(':pcode',$q->pcode);
                                    $stmt->bindValue(':lvl2',$location->loclvl2);
                                    $results = $stmt->execute();
                                    $values = collect();
                                    while ($row = $results->fetchArray()) {
                                        $values->add($row);
                                    }
                                    array_push($dataarray, [$values[0]['avg'], $values[0]['quotes'], $values[0]['cv'], $values[0]['min'], $values[0]['max'], $values[0]['mmr']]);
                            }
                            return [
                                'pcode' => $q->pcode,'pname' => $q->pname,'data' => $dataarray,
                            ];

                        });
                    }

                    if ($action == "download") {
                        return Excel::download(new HHReportNine($country,$quotes), 'HHReport7.xlsx');
                    } else {
                        $back = '<a href="javascript:history.back()">< Back</a>';
                        return view('household.reports.three', compact('locations','country','products','quotations_filtered','back'));
                    }
    }


}
