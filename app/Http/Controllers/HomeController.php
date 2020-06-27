<?php

namespace App\Http\Controllers;

use File;
use DB;
use Auth;
use Excel;
use App\Location;
use App\Transaction;
use App\User;
use App\Country;
use App\Household\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Maatwebsite\Excel\HeadingRowImport;
use App\Imports\LocationsImport;
use Carbon\Carbon;   

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    #configure step 1
    public function configure1()
    {
        return view('admin.configure1');
    }

    #system logs
    public function systemLogs()
    {
        $user = Auth::User();
        $transactions = Transaction::with('user')->orderBy('updated_at','DESC')->get();
        return view('admin.syslogs',compact('user', 'transactions'));
    }

    #account mgmt
    public function accountManagement()
    {
        $user = Auth::User();
        $users = User::All();
        $locations = Location::where('ccode',$user->country)->get();
        return view('admin.accounts',compact('user', 'users', 'locations'));
    }

    #account creation
    public function createAccount(Request $request)
    {
        $usercountry = Auth::User();
        $validator = Validator::make($request->toArray(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withInput(Input::all())->withError($validator->errors()->first());
        } else {
            $user = new User;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->role = $request->role;
            $user->country = $usercountry->country;
            $user->loc_scope = $request->loc_scope;
            $user->status = true;
            $user->last_login = Carbon::now()->toDateTimeString();
            $user->save();
        }
        LOGG('CREATE','created user '.$user->email.'.');
        return redirect()->back()->withSuccess('created account <b>'.$user->email.'</b>');
    }

    #edit account
    public function editAccount(Request $request)
    {
        $usercountry = Auth::User();
        $user = User::where('id',$request->user_id)->first();
        if ($user) {
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->role = $request->role;
            $user->country = $usercountry->country;
            $user->loc_scope = $request->loc_scope;
            $user->status = true;
            $user->last_login = Carbon::now()->toDateTimeString();
            $user->save();
        } else {
            return redirect()->back()->withInput(Input::all())->withError('No such user exists');
        }
        LOGG('UPDATE','edited user '.$user->email.'.');
        return redirect()->back()->withSuccess('updated account <b>'.$user->email.'</b>');
    }

    #delete account
    public function deleteAccount(Request $request)
    {
        $user = User::where('id',$request->uid)->first();
        if ($user) {
            $user->delete();
        } else {
            return redirect()->back()->withInput(Input::all())->withError('No such user exists');
        }
        LOGG('UPDATE','deleted user '.$user->email.'.');
        return redirect()->back()->withSuccess('deleted account <b>'.$user->email.'</b>');
    }

    #my account mgmt
    public function myAccount()
    {
        $user = Auth::User();
        $quotations = Quotation::where('encoder',$user->id)->count();
        return view('admin.myaccount',compact('user','quotations'));
    }

    #my account edit
    public function editMyAccount(Request $request)
    {
        $country = session('country');
        $validator = Validator::make($request->toArray(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email'],
            'question' => ['required', 'string'],
            'answer' => ['required', 'string'],
            'current_password' => ['required'],
            'password' => ['confirmed']
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withInput(Input::all())->withError($validator->errors()->first());
        } else {
            $user = User::where('email',$request->email)->first();
            if (Hash::check($request->current_password, $user->password)) {
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->email = $request->email;
                $user->question = $request->question;
                $user->answer = $request->answer;
                if ($request->password) {
                    $user->password = Hash::make($request->password);
                }
                $user->status = true;
                $user->save();
                LOGG('UPDATE','updated personal account details.');
                return redirect()->back()->withSuccess('Account update successful!');
            } else {
                return redirect()->back()->withError("The specified password was incorrect.");   
            }
        }
        
    }

    public function configureUploadData(Request $request) {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $filepath = $request->file('imported-file')->store('temp');
        $path = storage_path('app').'/'.$filepath; 

        $headings = (new HeadingRowImport)->toArray($path);
        if (in_array("icplocationtemplate", $headings[0][0])) {
            Location::where('ccode',Auth::User()->country)->delete();
            $import = new LocationsImport();
            Excel::import($import, $path);
            if ($import->getErrors()) {
                $errors = $import->getErrors();
                return redirect()->back()->with(compact('errors'));
            }
            $success = 'Successfully uploaded template containing <b>'.$import->getRowCount().'</b> records.';
            LOGG('UPDATE','uploaded HH location template with '.$import->getRowCount().' records.');
        } else {
            $errors = array(array('row' => '-', 'error' => 'The uploaded file is invalid. Please check that you are uploading the correct <b>product template.</b>'));
            return redirect()->back()->with(compact('errors'));
        }

        
        return redirect()->back()->with(compact('success'));
    }

    #config step2
    public function showConfigCountry(Request $request)
    {
        $countries = Country::all();
        $selected_country = false;
        if (Auth::User()) {
            $selected_country = Country::where('ccode',Auth::User()->country)->first();
        }
        return view('admin.config2', compact('countries','selected_country'));
    }
    #config step3
    public function showConfigLocations(Request $request)
    {
        $country = session('country');
        $ccode = $country->ccode;
        $locations = DB::table('locations as l')
                ->leftJoin('locations','l.id','=','locations.id')
                    ->select(
                        'l.id','l.loclvl','l.locname','l.loclvl2', 'l.loclvl3', 'l.loclvl4', 'l.loclvl5', 'l.capcity',
        DB::raw('l.loclvl2 || l.loclvl3 || l.loclvl4 || l.loclvl5 as LocationCode'),
        DB::raw('(select locname from locations where loclvl2=l.loclvl2 and loclvl3="00" and ccode="'.$ccode.'") as locname2'),
        DB::raw('(select locname from locations where loclvl2=l.loclvl2 and loclvl3=l.loclvl3 and loclvl4="00" and loclvl5="000" and loclvl="3" and ccode="'.$ccode.'") as locname3'),
        DB::raw('(select locname from locations where loclvl2=l.loclvl2 and loclvl3=l.loclvl3 and loclvl4=l.loclvl4 and loclvl5="000" and loclvl="4" and ccode="'.$ccode.'") as locname4'),
        DB::raw('(select locname from locations where loclvl2=l.loclvl2 and loclvl3=l.loclvl3 and loclvl4=l.loclvl4 and loclvl5=l.loclvl5  and loclvl="5" and ccode="'.$ccode.'") as locname5')
                        )
                    ->where('locations.ccode' ,'=' , $ccode)
            ->groupBy('locations.loclvl2' , 'locations.loclvl3' , 'locations.loclvl4', 'locations.loclvl5')
            ->get(); 
        if ($country->loclvl3 == "" && $country->loclvl4 == "" && $country->loclvl5 == "") {
            $country_loclvl = 2;    
        } else if ($country->loclvl4 == "" && $country->loclvl5 == "") {
            $country_loclvl = 3;
        } else if ($country->loclvl5 == "") {
            $country_loclvl = 4;    
        } else {
            $country_loclvl = 5;
        }
        return view('admin.config3', compact('country','locations','country_loclvl'));
    }


}

