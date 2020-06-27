<?php

namespace App\Imports;

use Auth;
use App\Helpers;
use App\Location;
use App\Household\Outlet;
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

class HHOutletsImport implements ToModel, WithStartRow
{
    public $count = 11;
    public $errorarray = [];
    public function __construct($upload_option)
    {
        $this->upload_option = $upload_option;
    }

    public function model(array $row)
    {
        $haserror = "";
        if ($row[0] != null || $row[1] != null || $row[2] != null || $row[3] != null || $row[4] != null || $row[5] != null || $row[6] != null || $row[7] != null || $row[8] != null) {
            $haserror = $this->validateRow($row);
        }
        if ($haserror == false && ($row[0] != null || $row[1] != null || $row[2] != null || $row[3] != null || $row[4] != null || $row[5] != null || $row[6] != null || $row[7] != null || $row[8] != null)) {
                $hhoutlet = Outlet::updateOrCreate([
                    'lvl2'     => force2digits($row[0]),
                    'lvl3'     => force2digits($row[1]),
                    'lvl4'     => force2digits($row[2]),
                    'lvl5'     => force3digits($row[3]),
                    'ocode'    => force3digits($row[4]),
                    'ccode'    => Auth::user()->country,
                ],[
                    'oname'    => $row[5],
                    'address'  => $row[6],
                    'otype'    => $row[7],
                    'loctype'  => $row[8]
                ]);
                return $hhoutlet;
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
        $lvl2 = force2digits($row[0]);
        $lvl3 = force2digits($row[1]);
        $lvl4 = force2digits($row[2]);
        $lvl5 = force3digits($row[3]);
        $ocode = $row[4];
        $otype = $row[7];
        $loctype = $row[8];
        $exists = Location::where('loclvl2',$lvl2)
                          ->where('loclvl3',$lvl3)
                          ->where('loclvl4',$lvl4)
                          ->where('loclvl5',$lvl5)
                          ->first();
        if (!$exists || $exists == null) {
            $error = $error.'Location code does not exist: <b>'.$lvl2.$lvl3.$lvl4.$lvl5.'</b><br>';
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

        if ($row[5]=="") {
            $error = $error.'Outlet name cannot be empty.<br>';
            $haserror = true;
        }
        if ($row[6]=="") {
            $error = $error.'Outlet address cannot be empty.<br>';
            $haserror = true;
        }

        if (!is_numeric($otype) || $otype > 9 || $otype < 1) {
            $error = $error.'Outlet type should be numerical digit from 1-9. Found: <b>'.$otype.'</b><br>';
            $haserror = true;
        }
        if (!is_numeric($loctype) || ($loctype != 1 && $loctype != 2)) {
            $error = $error.'Location type should be numerical digit from 1-2. Found: <b>'.$loctype.'</b><br>';
            $haserror = true;
        }

        // $outlet_exists = Outlet::where('lvl2',$lvl2)
        //                         ->where('lvl3',$lvl3)
        //                         ->where('lvl4',$lvl4)
        //                         ->where('lvl5',$lvl5)
        //                         ->where('ocode',force3digits($row[4]))->first();
        // if ($outlet_exists) {
        //     $error = $error.'The specified outlet code already exists: <b>'.force3digits($row[4]).'</b><br>';
        //     $haserror = true;
        // }
        
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
