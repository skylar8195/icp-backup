<?php

namespace App\Imports;

use App\Household\Product;
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

class HHProductsImport implements ToModel, WithStartRow
{
    public $count = 11;
    public $errorarray = [];

    public function model(array $row)
    {
        $haserror = $this->validateRow($row);
        if ($haserror == false) {
          $hhproduct = Product::where('pcode',str_replace(".","",$row[0]))->first();
          $hhproduct->avail = $row[2];
          $hhproduct->survperiod = $row[3];
          $hhproduct->save();
          return $hhproduct;
        }
    }

    public function startRow(): int
    {
        return 11;
    }

    #validates row data
    public function validateRow($row) {
        $count = $this->count;
        $haserror = false;
        $error =  "";   
        $pcode = str_replace(".","",$row[0]);
        $avail = $row[2];
        $freq  = $row[3];
        $exists = Product::where('pcode',$pcode)->first();
        if (!$exists) {
            $error = 'Product code does not exist: '.$pcode.'<br>';
            $haserror = true;
        }
        if ($avail != "0" && $avail != '1' && $avail != '2') {
            ($avail == null) ? $avail = '_' : $avail = $avail;
            $error = $error.'Availability must be 0, 1, or 2. Found: <b>'.$avail.'</b><br>';
            $haserror = true;
        }
        if ($freq != "M" && $freq != 'Q' && $freq != 'S' && $freq != 'A') {
            if ($avail == "0" && $freq == "") {
              //no error
            } else {
              ($freq == null) ? $freq = '_' : $freq = $freq;
              $error = $error.'Frequency must be M, Q, S, or A. Found: <b>'.$freq.'</b>';
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
        ++$this->count;
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
