<?php

namespace App\Http\Controllers;

use Config;
use Artisan;
use Illuminate\Support\Facades\Storage;
use File;
use Excel;
use DB;
use Auth;
use App\Country;
use App\User;
use App\Location;
use App\Household\Product;
use App\Household\ProductClassification;
use App\Household\Quotation;
use App\Exports\LocationsExport;
use App\Imports\LocationsImport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class PublicController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('web');
    }

    public function overview() {
        $page = 'overview';
        return view('pages.overview', compact('page'));
    }

    public function history() {
        $page = 'history';
        return view('pages.history', compact('page'));
    }

    public function governance() {
        $page = 'governance';
        return view('pages.governance', compact('page'));
    }

    public function basicConcepts() {
        $page = 'basic-concepts';
        return view('pages.basic-concepts', compact('page'));
    }

    public function termsOfUse() {
        $page = 'terms';
        return view('pages.terms', compact('page'));
    }
}
