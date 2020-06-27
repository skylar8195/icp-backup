<?php

namespace App\Imports;

use Auth;
use App\Location;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;

use Maatwebsite\Excel\Concerns\RegistersEventListeners;

class LocationsImport implements ToModel, WithStartRow, WithEvents
{
    use RegistersEventListeners;

    #array for errors
    public $count = 9;
    public $errorarray = [];

    #check if not empty
    public static function beforeImport(BeforeImport $event)
    {
        $worksheet = $event->reader->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        $errorarray = [];
        if ($highestRow < 9) {
            $errors = 'The uploaded file is empty.';
            return redirect()->back()->with(compact('errors'));
        }
    }


    #specifies starting row number
    public function startRow(): int
    {
        return 9;
    }

    #manipulate uploaded data
    public function model(array $row)
    {
        $haserror = "";
        $country = session('country');
        if ($row[0] != null) {
            $haserror = $this->validateRow($row);
        }
        if ($haserror == false && ($row[0] != null || $row[1] != null || $row[2] != null || $row[3] != null || $row[4] != null || $row[5] != null)) {

                if ($country->loclvl3 == "") { $row[1] = "00"; }
                if ($country->loclvl4 == "") { $row[2] = "00"; }
                if ($country->loclvl5 == "") { $row[3] = "000"; }

                $lvl2 = force2digits($row[0]);
                $lvl3 = force2digits($row[1]);
                $lvl4 = force2digits($row[2]);
                $lvl5 = force3digits($row[3]);
                $loclvl = $this->getLocLvl($lvl2,$lvl3,$lvl4,$lvl5);
                if (strtolower($row[5]) == "yes") {
                    $capcity = 1;
                } else {
                    $capcity = 0;
                }
                $hhloc = Location::updateOrCreate([
                    'ccode'       => $country->ccode,
                    'loclvl2'     => $lvl2,
                    'loclvl3'     => $lvl3,
                    'loclvl4'     => $lvl4,
                    'loclvl5'     => $lvl5,
                    'loclvl'      => $loclvl
                ],[
                    'locname'     => $row[4],
                    'capcity'     => $capcity
                ]);

                ++$this->count;
                return $hhloc;
        }
    }

    #validates row data
    public function validateRow($row) {
        $country = session('country');
        $count = $this->count;
        $haserror = false;
        $error =  "";   
        $lvl2 = force2digits($row[0]);
        $lvl3 = force2digits($row[1]);
        $lvl4 = force2digits($row[2]);
        $lvl5 = force3digits($row[3]);
        $locname = $row[4];

        if (strlen($lvl2) != 2 || !is_numeric($lvl2) || $lvl2 == "00") {
            $error = $error.'Location code 2 should be 2 numerical digits. Found: <b>'.$lvl2.'</b><br>';
            $haserror = true;
        }
        if (strlen($lvl3) != 2 || !is_numeric($lvl3) && $country->loclvl3 != "") {
            $error = $error.'Location code 3 should be 2 numerical digits. Found: <b>'.$lvl3.'</b><br>';
            $haserror = true;
        }
        if (strlen($lvl4) != 2 && !is_numeric($lvl4) && $country->loclvl4 != "") {
            $error = $error.'Location code 4 should be 2 numerical digits. Found: <b>'.$lvl4.'</b><br>';
            $haserror = true;
        }
        if (strlen($lvl5) != 3 && !is_numeric($lvl5) && $country->loclvl5 != "") {
            $error = $error.'Location code 5 should be 2 numerical digits. Found: <b>'.$lvl5.'</b><br>';
            $haserror = true;
        }
        if ($locname == "") {
            $error = $error.'Location name is requied.<br>';
            $haserror = true;
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

    public function getLocLvl($lvl2,$lvl3,$lvl4,$lvl5) {
        if ($lvl2 != "00" && $lvl3 == "00" && $lvl4 == "00" && $lvl5 == "00") {
            return "2";
        } else if ($lvl2 != "00" && $lvl3 != "00" && $lvl4 == "00" && $lvl5 == "00") {
            return "3";
        } else if ($lvl2 != "00" && $lvl3 != "00" && $lvl4 != "00" && $lvl5 == "00") {
            return "4";
        } else if ($lvl2 != "00" && $lvl3 != "00" && $lvl4 != "00" && $lvl5 != "00") {
            return "5";
        }
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
