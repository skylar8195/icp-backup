<?php

namespace App\Exports\Reports;

use Auth;
use App\Country;
use App\Exports\Reports\HHReportSixANX1;
use App\Exports\Reports\HHReportSixANX2;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HHReportEight implements WithMultipleSheets
{

	//parameters
   	public function __construct($country,$quotations)
   	{
   		$this->country = $country;
      $this->quotations = $quotations;
   	}

    /**
    * @return array
    */
   public function sheets(): array
   {
       $sheets = [];
       $sheets[] = new HHReportSixANX1($this->country,$this->quotations);
       $sheets[] = new HHReportSixANX2($this->country,$this->quotations);
       return $sheets;
   }
}
