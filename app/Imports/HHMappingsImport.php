<?php

namespace App\Imports;

use Auth;
use App\Helpers;
use App\Household\Product;
use App\Household\Outlet;
use App\Household\Mapping;
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

class HHMappingsImport implements ToModel, WithStartRow
{
    public $count = 11;
    public $errorarray = [];
    public function model(array $row)
    {
        $haserror = "";
        if ($row[0] != null || $row[1] != null || $row[2] != null || $row[3] != null || $row[4] != null || $row[5] != null) {
            $lvl2 = force2digits($row[0]);
            $lvl3 = force2digits($row[1]);
            $lvl4 = force2digits($row[2]);
            $lvl5 = force3digits($row[3]);
            $ocode = force3digits($row[4]);
            $pcode = stripDots($row[5]);
            $outlet_exists = Outlet::where('lvl2',$lvl2)
                                  ->where('lvl3',$lvl3)
                                  ->where('lvl4',$lvl4)
                                  ->where('lvl5',$lvl5)
                                  ->where('ocode',$ocode)
                                  ->first();
            $haserror = $this->validateRow($row,$lvl2,$lvl3,$lvl4,$lvl5,$ocode,$pcode,$outlet_exists);
        }
        if ($haserror == false) {
          $hhmapping = Mapping::updateOrCreate([
              'ocode'   => $outlet_exists->ocode,
              'oid'     => $outlet_exists->id,
              'pcode'   => $pcode
          ],[

          ]);
          ++$this->count;
          return $hhmapping;
        }
    }

    public function startRow(): int
    {
        return 11;
    }

    #validates row data
    public function validateRow($row,$lvl2,$lvl3,$lvl4,$lvl5,$ocode,$pcode,$outlet_exists) {
        $count = $this->count;
        $haserror = false;
        $error =  "";
        
        if (!$outlet_exists) {
            $error = $error.'Outlet does not exist: <b>'.$lvl2.$lvl3.$lvl4.$lvl5.$ocode.'</b><br>';
            $haserror = true;
        }
        $pcode_exists = Product::where('pcode',$pcode)->first();
        if (!$pcode_exists) {
            $error = $error.'Product code does not exist: <b>'.$pcode.'</b><br>';
            $haserror = true;
        }

        if (strlen($lvl2) != 2 || !is_numeric($lvl2)) {
            $error = $error.'Location code 2 should be 2 numerical digits. Found: <b>'.$lvl2.'</b><br>';
            $haserror = true;
        }
        if (strlen($lvl3) != 2 || !is_numeric($lvl3)) {
            $error = $error.'Location code 3 should be 2 numerical digits. Found: <b>'.$lvl3.'</b><br>';
            $haserror = true;
        }
        if (strlen($lvl4) != 2 && !is_numeric($lvl4)) {
            $error = $error.'Location code 4 should be 2 numerical digits. Found: <b>'.$lvl4.'</b><br>';
            $haserror = true;
        }
        if (strlen($lvl5) != 3 && !is_numeric($lvl5)) {
            $error = $error.'Location code should be 3 numerical digits. Found: <b>'.$lvl5.'</b><br>';
            $haserror = true;
        }
        if (strlen($ocode) != 3 && !is_numeric($ocode)) {
            $error = $error.'Outlet code should be 3 numerical digits. Found: <b>'.$ocode.'</b><br>';
            $haserror = true;
        }
        
        if ($pcode_exists) {
          if ($pcode_exists->avail == 0 || $pcode_exists->survperiod == "" || $pcode_exists->survperiod == null) {
              $error = $error.'Product is not available or does not have a frequency specified: <b>'.$pcode.'</b><br>';
              $haserror = true;
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
        return ($this->count)-11;
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
