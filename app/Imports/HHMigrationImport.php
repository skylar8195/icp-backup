<?php

// namespace App\Imports;

// use Maatwebsite\Excel\Concerns\WithMultipleSheets;
// use App\Imports\MigrationsSheets\ProductMigration;
// use App\Imports\MigrationsSheets\OutletLocationMigration;
// use App\Imports\MigrationsSheets\MappingMigration;

// class HHMigrationImport implements WithMultipleSheets 
// {
//     public function sheets(): array
//     {
//         return [
//             0 => new ProductMigration(),
//             1 => new OutletLocationMigration(),
//             2 => new MappingMigration()
//         ];
//     }
// }


namespace App\Imports;
use Carbon\Carbon;
use DB;
use Auth;
use App\Country;
use App\Location;
use App\Household\Outlet;
use App\Household\Mapping;
use App\Household\Quotation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithConditionalSheets;
use App\Imports\MigrationsSheets\ProductMigration;
use App\Imports\MigrationsSheets\OutletLocationMigration;
use App\Imports\MigrationsSheets\MappingMigration;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithChunkReading;

use App\Household\Product;

class HHMigrationImport implements ToCollection, WithChunkReading
{

	public function chunkSize(): int
     {
        return 61000;
     }

    public function collection(Collection $collection)
    {
    		DB::disableQueryLog();
		// if ($collection[1][1] == "Product Reference File") {
		// 	for ($i = 9; $i < count($collection); $i++) {
		// 		$prod = Product::where('concordance2017',$collection[$i][0])->update(['avail'=>$collection[$i][1]]);
		// 	}
		// } else if ($collection[1][2] == "Location/Outlet Reference File") {
		// 	$c = Country::where('ccode',Auth::User()->country)->first();
		// 	if ($collection[8][1] == null || $collection[8][1] == "") {
		// 		$c->loclvl3 = '';
		// 		$c->loclvl4 = '';
		// 		$c->loclvl5 = '';
		// 		$c->save();
		// 	}
		// 	if ($collection[8][2] == null || $collection[8][2] == "") {
		// 		$c->loclvl4 = '';
		// 		$c->loclvl5 = '';
		// 		$c->save();
		// 	}
		// 	if ($collection[8][3] == null || $collection[8][3] == "") {
		// 		$c->loclvl5 = '';
		// 		$c->save();
		// 	}
		// 	for ($i = 9; $i < count($collection); $i++) {

		// 		        	$lvl2 = force2digits($collection[$i][0]);
		// 		   		$lvl3 = force2digits($collection[$i][1]);
		// 		   		$lvl4 = force2digits($collection[$i][2]);
		// 		   		$lvl5 = force3digits($collection[$i][3]);

		// 		        	if ($collection[$i][4] == 0) {
		// 		        		if ($lvl3 == "0" && $lvl4 == "0" && $lvl5 == "0") {
		// 		        			$loclvl = "2";
		// 		        		} else if ($lvl3 != "0" && $lvl4 == "0" && $lvl5 == "0") {
		// 						$loclvl = "3";
		// 		        		} else if ($lvl3 != "0" && $lvl4 != "0" && $lvl5 == "0") {
		// 		        			$loclvl = "4";
		// 		        		} else if ($lvl3 != "0" && $lvl4 != "0" && $lvl5 != "0") {
		// 		        			$loclvl = "5";
		// 		        		}
		// 		        		if ($lvl2 != "00") {
		// 		        		$location = new Location;
		// 		        		$location->loclvl2 = $lvl2;
		// 		        		$location->loclvl3 = $lvl3;
		// 		        		$location->loclvl4 = $lvl4;
		// 		        		$location->loclvl5 = $lvl5;
		// 		        		$location->locname = $collection[$i][5];
		// 		        		$location->loclvl  = $loclvl;
		// 		        		$location->ccode = Auth::User()->country;
		// 		        		$location->save();
		// 		        		}
		// 		        	} else {
		// 		        		$outlet = new Outlet;
		// 		        		$outlet->ocode = force3digits($collection[$i][4]);
		// 		        		$outlet->oname = $collection[$i][5];
		// 		        		$outlet->ccode = Auth::User()->country;
		// 		        		$outlet->lvl2 = $lvl2;
		// 		        		$outlet->lvl3 = $lvl3;
		// 		        		$outlet->lvl4 = $lvl4;
		// 		        		$outlet->lvl5 = $lvl5;
		// 		        		$outlet->address = $collection[$i][6];
		// 		        		$outlet->otype = $collection[$i][7];
		// 		        		$outlet->loctype = $collection[$i][8];
		// 		        		$outlet->save();
		// 		        	}
		// 	}
		// } else if ($collection[1][2] == "Outlet-Product Mapping") {
		// 	for ($i = 9; $i < count($collection); $i++) {
		// 		$lvl2 = force2digits($collection[$i][0]);
	 //        		$lvl3 = force2digits($collection[$i][1]);
	 //        		$lvl4 = force2digits($collection[$i][2]);
	 //        		$lvl5 = force3digits($collection[$i][3]);
	 //        		$ocode = force3digits($collection[$i][4]);
	 //        		$pcode = $collection[$i][5];

	 //        		$outlet = Outlet::where('lvl2',$lvl2)->where('lvl3',$lvl3)->where('lvl4',$lvl4)->where('lvl5',$lvl5)->where('ocode',$ocode)->first();
	 //        		$product = Product::where('concordance2017',$pcode)->first();

	 //        		if ($outlet && $product) {
	 //        			$mapping = new Mapping;
		//         		$mapping->ocode = $ocode;
		//         		$mapping->oid   = $outlet->id;
		//         		$mapping->pcode = $product->pcode;	
		// 			$mapping->save();
	 //        		}
	 //        	}
		// } 
		// else if ($collection[1][2] == "Observed Data") {
    		if ($collection[1][2] == "Observed Data") {
			$count = 0;
			for ($i = 9; $i < count($collection); $i++) {
				$lvl2 = force2digits($collection[$i][1]);
	        		$lvl3 = force2digits($collection[$i][2]);
	        		$lvl4 = force2digits($collection[$i][3]);
	        		$lvl5 = force3digits($collection[$i][4]);
	        		$ocode = force3digits($collection[$i][5]);
	        		$outlet = Outlet::where('lvl2',$lvl2)->where('lvl3',$lvl3)->where('lvl4',$lvl4)->where('lvl5',$lvl5)->where('ocode',$ocode)->first();
	        		
	        		if ($outlet) {
		        		$oid = $outlet->id;
		        		$pcode = $collection[$i][6];
		        		$product = Product::where('concordance2017',$pcode)->first();
		        		// $refperiod = "";
		        		// switch (substr($collection[$i][0],0,2)) {
		        		// 	case "MN":
		        		// 		$refperiod = "M";
		        		// 		break;
		        		// 	case "QR":
		        		// 		$refperiod = "M";
		        		// 		break;
		        		// 	case "SA":
		        		// 		$refperiod = "S";
		        		// 		break;
		        		// 	case "AN":
		        		// 		$refperiod = "A";
		        		// 		break;
		        		// }
		        		$uid = Auth::User()->id;
		        		if ($product) {
			        		// $qq = new Quotation;
			        		// $qq->oid			= $oid;
			        		// $qq->ocode		= force3digits($ocode);
			        		// $qq->pcode		= round($product->pcode,0)."";
			        		// $qq->obv_date		= $collection[$i][7];
			        		// $qq->obv_qty		= $collection[$i][8];
			        		// $qq->price		= $collection[$i][10];
			        		// $qq->ref_period	= $refperiod;
			        		// $qq->ref_year		= '2020';
			        		// $qq->ref_months	= substr($collection[$i][7],0,2);
			        		// $qq->brand		= '';
			        		// $qq->con_price		= $collection[$i][12];
			        		// $qq->price_type	= $collection[$i][11];
			        		// $qq->remarks		= '';
			        		// $qq->encoder		= Auth::User()->id;
			        		// $qq->save();
			        		DB::insert('INSERT INTO household_quotations (oid, pcode, obv_date, obv_qty, price, ref_period, ref_year, ref_months, brand, con_price, price_type, remarks, encoder) VALUES ('.$oid.','.round($product->pcode,0).', "'.transformDate($collection[$i][7]).'",'.$collection[$i][8].','.$collection[$i][10].', "M", "2020",'.substr($collection[$i][7],0,2).',"",'.$collection[$i][12].', "'.$collection[$i][11].'","",'.$uid.')');
		        		}
		        	}
	        		$count++;
	        	}
		}

    }//end collection

}	