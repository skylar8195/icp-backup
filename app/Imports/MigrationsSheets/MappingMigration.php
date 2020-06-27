<?php

namespace App\Imports\MigrationsSheets;

use Auth;
use App\Location;
use App\Household\Product;
use App\Household\Outlet;
use App\Household\Mapping;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class MappingMigration implements ToCollection, WithStartRow
{
	public $count = 10;
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
        		$lvl2 = force2digits($row[0]);
        		$lvl3 = force2digits($row[1]);
        		$lvl4 = force2digits($row[2]);
        		$lvl5 = force3digits($row[3]);
        		$ocode = force3digits($row[4]);
        		$pcode = $row[5];

        		$outlet = Outlet::where('lvl2',$lvl2)->where('lvl3',$lvl3)->where('lvl4',$lvl4)->where('lvl5',$lvl5)->where('ocode',$ocode)->first();
        		$product = Product::where('pcode',$pcode)->first();

        		if ($outlet && $product) {
        			$mapping = new Mapping;
	        		$mapping->ccode = Auth::User()->country;
	        		$mapping->ocode = $ocode;
	        		$mapping->oid   = $outlet->id;
	        		$mapping->pcode = $product->pcode;	
				$mapping->save();
        		}

        		++$this->count;
        }
    }

    public function startRow(): int
    {
        return 10;
    }
}
