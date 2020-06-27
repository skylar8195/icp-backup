<?php

namespace App\Imports;

use Auth;
use App\Helpers;
use App\Household\Product;
use App\Household\Outlet;
use App\Household\Quotation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class HHSurveyImport implements ToModel, WithStartRow
{
    public $count = 9;
    public $errorarray = [];

    //construct variable
   public function __construct()
   {
   	$this->ref_period = "";
   	$this->ref_year = "";
   	$this->ref_months = "";
   }

    public function model(array $row)
    {
    	   $haserror = "";
    	   if ($row[0] != 'skip' && $row[0] != 'ref_period' && $row[0] != 'ref_year' && $row[0] != 'ref_months') {
    	   	$haserror = $this->validateRow($row);
    	   	++$this->count;
    	   }
        if ($row[0] == 'ref_period' || $row[0] == 'ref_year' || $row[0] == 'ref_months') {
        	if ($row[0] == 'ref_period') {
        		$this->ref_period = $row[1];
        	} else if ($row[0] == 'ref_year') {
        		$this->ref_year = $row[1];
        	} else if ($row[0] == 'ref_months') {
        		$this->ref_months = $row[1];
        	}
        }
        if ($haserror == false && $row[0] != 'skip' && $row[0] != 'ref_period' && $row[0] != 'ref_year' && $row[0] != 'ref_months') {
        		 $prod = Product::where('pcode',$row[3])->first();
        		 $refperiod = $this->ref_period;
        		 $refyear = $this->ref_year;
        		 $refmonths = $this->ref_months;
        		 $outlet = Outlet::find($row[1]);
                $hhquotation = Quotation::updateOrCreate([
                	'oid'		=> round($row[1],0)."",
                	'ocode'   	=> force3digits($outlet->ocode),
                    'pcode'   	=> round($row[3],0)."",
                    'obv_date'	=> $row[15].'/'.$row[14].'/'.$row[16],
                    'obv_qty'	 	=> $row[11],
                    'price'	 	=> $row[12],
                    'ref_period'	=> $refperiod,
                    'ref_year'	=> round($refyear,0)."",
                    'ref_months'	=> $refmonths,
                    'brand'		=> $row[17]
                ],[
                	'con_price'    => (floatval($row[12])/floatval($row[11]))*floatval($prod->prefqty),
                    'price_type'   => $row[13],
                    'remarks'		=> $row[18],
                    'encoder'		=> Auth::User()->id
                ]);
                return $hhquotation;
        }
    }

    public function startRow(): int
    {
        return 4;
    }

    #validates row data
    public function validateRow($row) {
        $count = $this->count;
        $haserror = false;
        $error =  "";   
        if ($row[0] != 'skip' && $row[0] != 'ref_period' && $row[0] != 'ref_year' && $row[0] != 'ref_months') {
        		
        	$minqty	= $row[9];
	        $maxqty 	= $row[10];
	        $obv_qty 	= $row[11];
	        $price 	= $row[12];
	        $price_type = $row[13];
	        $day 		= $row[14];
	        $month 	= $row[15];
	        $year 		= $row[16];
	        $otherinfo 	= $row[17];
	        $remarks 	= $row[18];
        
	        if (!is_numeric($obv_qty)) {
	            $error = 'Quantity must be a number. Found '.$obv_qty.'<br>';
	            $haserror = true;
	        }
	        if (($obv_qty > $maxqty) || ($obv_qty < $minqty)) {
	            $error = 'Quantity must fall within range <b>'.$minqty.'-'.$maxqty.'</b>. Found '.$obv_qty.'<br>';
	            $haserror = true;
	        }
	        if (!is_numeric($price)) {
	            $error = $error.'Price must be a number. Found: <b>'.$price.'</b><br>';
	            $haserror = true;	
	        }
	        if ($price_type != "R" && $price_type != 'B' && $price_type != 'D' && $price_type != "r" && $price_type != 'b' && $price_type != 'd') {
	            $error = $error.'Price type must be <b>R</b> (regular), <b>B</b> (bargain), or <b>D</b> (discounted). Found: <b>'.$price_type.'</b><br>';
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
	        $existing_quotation = Quotation::where('pcode',round($row[3],0)."")
	        							->where('oid',round($row[1],0)."")
	        							->where('ref_period',$this->ref_period)
	        							->where('ref_year',$this->ref_year)
	        							->where('ref_months',$this->ref_months)
	        							->first();
		  if ($existing_quotation) {
		  	$prod = Product::where('pcode',$existing_quotation->pcode)->first();
		  	if (strpos($prod->pname, 'WKB') !== false) {
		  		if ($row[17] == null || $row[17] == "") {
		  			$error = $error.'Please specify brand for this WKB product.';
	          		$haserror = true;
		  		}
			} else {
				$error = $error.'Existing quotation already exists for specified reference period (<b>'.$this->ref_year.$this->ref_period.$this->ref_months.'</b>). Please edit/delete the existing quotation using the system.';
	          	$haserror = true;
			}
		  }
   	   }
        if ($haserror) {
          $error = [
              'row' => $count,
              'error' => $error
          ];
          array_push($this->errorarray,$error);
        }
        return $haserror;
    }

    public function getRowCount(): int
    {
        return ($this->count)-9;
    }

    public function getErrors()
    {
        if (count($this->errorarray)>0) {
          return $this->errorarray;
        } else {
          return false;
        }   
    }
}
