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

use GuzzleHttp\Client;


class DataSearchController extends Controller
{
    //
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('web');
    }

    public function dataSearchFilter() {
        $page = 'dataSearchFilter';
        return view('pages.data-search-filter', compact('page'));
    }

    function getElementDetails($countries, $referenceColumn, $referenceValue,$elementName){                
         foreach ($countries as $rowKey => $rowValue) {                      
            if(strcasecmp($rowValue[$referenceColumn],$referenceValue)==0){                
                $newRowValue = array();
                foreach ($rowValue as $key => $value) {    
                    $newKey = $elementName . $key;                    
                    $newRowValue += [$newKey => $value] ;                   
                }                
                return  $newRowValue;
            }
        }  
        return null;
    }

    function getElementRow($dataSource){
         // var_dump($mainValue['id']);
         //    var_dump(json_encode($selectedPublicationArray));
         //    echo '<pre>' , var_dump(in_array($mainValue['id'], $selectedPublicationArray)) , '</pre>';
        $tempContainer=[];
        foreach ($dataSource as $key => $value){
            if($key!="publication"){
                // echo '<pre>' ,var_dump($key) , var_dump($value) , '</pre>';
                $tempContainer += [$key."" => $value];                 
            }
        }                
        return($tempContainer);

    }

    public function dataSearchResult(Request $request) {
        $page = 'dataSearchResult';        
        $selectedCountries = $request->selectedCountries;
        $selectedIndicators = $request->selectedIndicators;
        $selectedPublications = $request->selectedPublications;
        $selectedYears = $request->selectedYears;
        $selectedView = $request->selectedView;

        $dataItem = json_decode(DATA_LIST, true);
        $dataItem = array_filter($dataItem);

        $optionCountries = json_decode(COUNTRY_LIST, true);
        $optionCountries = array_filter($optionCountries);        
        $optionCountries = collect($optionCountries)->whereIn("id",explode(":",$selectedCountries))->all();

        $selectedCountry=[];        
        if($request->selectedCountry)
            $selectedCountry = explode(',',$request->selectedCountry);        
        if(count($selectedCountry)==0){            
             foreach ($optionCountries as $key => $value) {              
                 array_push ($selectedCountry,$value['code']);
             }
        }

        $dataItem = collect($dataItem)
                        ->whereIn("country",$selectedCountry)                        
                    ->all();

        $optionIndicators = json_decode(INDICATOR_LIST, true);
        $optionIndicators = array_filter($optionIndicators);                
        $optionIndicators = collect($optionIndicators)->whereIn("id",explode(":",$selectedIndicators))->all();

        
        $optionPublications = json_decode(PUBLICATION_LIST, true);        
        $optionPublications = array_filter($optionPublications);
        // $optionPublications = collect($optionPublications)->whereIn("id",explode(":",$selectedPublications))->all();
        // var_dump("expression");

        // dd($optionPublications);
        $selectedPublicationArray=explode(":",$selectedPublications);
        // empty($optionPublications);
        $optionPublicationsContainer=[];
        foreach ($optionPublications  as $mainKey => $mainValue) {                 
            if (in_array($mainValue['id'], $selectedPublicationArray)){                
                array_push($optionPublicationsContainer,$this->getElementRow($mainValue));
            }
            if (array_key_exists('publication', $mainValue)) {                            
                foreach ($mainValue['publication']  as $subKey => $subValue) {                    
                    if (in_array($subValue['id'], $selectedPublicationArray)){
                        array_push($optionPublicationsContainer,$this->getElementRow($subValue));
                    }
                    if (array_key_exists('publication', $subValue)) {
                        foreach ($subValue['publication']  as $subSubKey => $subSubValue) {
                            if (in_array($subSubValue['id'], $selectedPublicationArray)){
                                array_push($optionPublicationsContainer,$this->getElementRow($subSubValue));
                            }
                        }
                    }
                }
            }                       
        }

        $optionPublications =$optionPublicationsContainer;

        $selectedPublication=[];        
        if($request->selectedPublication)
            $selectedPublication = explode(',',$request->selectedPublication);
        if(count($selectedPublication)==0){            
             foreach ($optionPublications as $key => $value) {              
                 array_push ($selectedPublication,$value['id']);
             }
        }        
        $dataItem = collect($dataItem)
                        ->whereIn("category",$selectedPublication)                        
                    ->all();
        
        $optionYears = json_decode(YEAR_LIST, true);        
        $optionYears = array_filter($optionYears);
        $optionYears = collect($optionYears)->whereIn("id",explode(':',$selectedYears))->all();
        $selectedYear =[];
        // dd($optionYears);
        foreach ($optionYears as $key => $value) {              
             array_push ($selectedYear,$value['id']);
         }

         $dataItem = collect($dataItem)
                        ->whereIn("year",$selectedYear)
                    ->all();                
        // loop in countries then get data publication then indicator then years
        $dataValue=[];
        // dd(json_encode($optionPublications));
        // foreach ($optionCountries as $countrykey => $countryvalue) {
        //     $newRowValue = $countryvalue;
        //     foreach ($optionPublications as $publicationkey => $publicationvalue) {

        //     });
        // });

        
        foreach ($optionCountries as $countrykey => $countryvalue) {
            // $starter = $this->getElementDetails($optionCountries,"code",$row["country"],"country");            
            $newRowValue = $countryvalue;                                    
            $publicationList =[];
            foreach ($optionPublications as $publicationkey => $publicationvalue) {                
                $newPublicationRow = $this->getElementRow($publicationvalue);                
                $indicatorList=[];
                foreach ($optionIndicators as $indicatorkey => $indicatorvalue) {                 
                    $indicatorRowValue = $indicatorvalue;
                    $rowDataValues = [];
                    $rowTmpValues = [];
                    foreach ($optionYears as $yearkey => $yearvalue) {
                        $itemDataValue = collect($dataItem)
                                ->where("country",$countryvalue["code"])
                                ->where("category",$publicationvalue["id"])                                
                                ->where("year",$yearvalue["id"])                                
                            ->first();                                                    
                        if($itemDataValue)
                            $rowTmpValues +=  [$yearvalue["id"]=>$itemDataValue[$indicatorvalue["id"]]];
                            // $indicatorRowValue += [$yearvalue["id"]=>$itemDataValue[$indicatorvalue["id"]]];
                        else
                            $rowTmpValues += [$yearvalue["id"]=>"no value"];
                            // $indicatorRowValue += [$yearvalue["id"]=>"no value"];

                    }                    
                    $indicatorRowValue += ["values"=>$rowTmpValues];
                    // array_push($indicatorRowValue, $rowDataValues);
                    array_push($indicatorList, $indicatorRowValue);
                }
                $newPublicationRow += ["indicators"=>$indicatorList];       
                array_push($publicationList, $newPublicationRow);
            }

            $newRowValue += ["publications"=>$publicationList];         
            array_push($dataValue,$newRowValue);
        }        
        // dd($dataValue);
        $dataValueJson =json_encode($dataValue);     
        // dd($dataValueJson);

        return view('pages.data-search-result', compact('page','optionCountries','optionIndicators','optionPublications','optionYears','dataItem','dataValue','dataValueJson'));
    }
}
