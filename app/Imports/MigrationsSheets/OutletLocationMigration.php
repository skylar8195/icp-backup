<?php

namespace App\Imports\MigrationsSheets;

use Auth;
use App\Location;
use App\Country;
use App\Household\Outlet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class OutletLocationMigration implements ToCollection, WithStartRow
{
	public $count = 10;
	public function __construct()
     {
        $this->firstrowdone = false;
     }
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

        	if ($this->firstrowdone == false) {
        		$c = Country::where('ccode',Auth::User()->country)->first();
        		if ($row[1] == null || $row[1] == "") {
        			$c->loclvl3 = '';
        			$c->save();
        		}
        		if ($row[2] == null || $row[2] == "") {
        			$c->loclvl4 = '';
        			$c->save();
        		}
        		if ($row[3] == null || $row[3] == "") {
        			$c->loclvl5 = '';
        			$c->save();
        		}
        		$this->firstrowdone = true;
        	}

        	$lvl2 = force2digits($row[0]);
   		$lvl3 = force2digits($row[1]);
   		$lvl4 = force2digits($row[2]);
   		$lvl5 = force3digits($row[3]);

        	if ($row[4] == 0) {
        		if ($lvl3 == "0" && $lvl4 == "0" && $lvl5 == "0") {
        			$loclvl = "2";
        		} else if ($lvl3 != "0" && $lvl4 == "0" && $lvl5 == "0") {
				$loclvl = "3";
        		} else if ($lvl3 != "0" && $lvl4 != "0" && $lvl5 == "0") {
        			$loclvl = "4";
        		} else if ($lvl3 != "0" && $lvl4 != "0" && $lvl5 != "0") {
        			$loclvl = "5";
        		}

        		$location = new Location;
        		$location->loclvl2 = $lvl2;
        		$location->loclvl3 = $lvl3;
        		$location->loclvl4 = $lvl4;
        		$location->loclvl5 = $lvl5;
        		$location->locname = $row[5];
        		$location->loclvl  = $loclvl;
        		$location->ccode = Auth::User()->country;
        		$location->save();

        	} else {
        		$outlet = new Outlet;
        		$outlet->ocode = force3digits($row[4]);
        		$outlet->oname = $row[5];
        		$outlet->ccode = Auth::User()->country;
        		$outlet->lvl2 = $lvl2;
        		$outlet->lvl3 = $lvl3;
        		$outlet->lvl4 = $lvl4;
        		$outlet->lvl5 = $lvl5;
        		$outlet->address = $row[6];
        		$outlet->otype = $row[7];
        		$outlet->loctype = $row[8];
        		$outlet->save();
        	}
        	++$this->count;
        }
    }

    public function startRow(): int
    {
        return 9;
    }
}