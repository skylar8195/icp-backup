<?php

namespace App\Exports\Reports;

use Auth;
use App\Country;
use App\Exports\Reports\HHReportSixSS;
use App\Exports\Reports\HHReportSixSOT;
use App\Exports\Reports\HHReportSixSum;
use App\Exports\Reports\HHReportSixANX1;
use App\Exports\Reports\HHReportSixAXN2;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HHReportSix implements WithMultipleSheets
{

	//parameters
   	public function __construct($country,$filtered_data,$products,$start_date,$end_date)
   	{
   		$this->country = $country;
   		$this->filtered_data = $filtered_data;
   		$this->products = $products;
      $this->start_date = $start_date;
      $this->end_date = $end_date;
   	}

    /**
    * @return array
    */
   public function sheets(): array
   {
       $sheets = [];
       $sheets[] = new HHReportSixSum($this->country,$this->products,$this->start_date,$this->end_date);
       $sheets[] = new HHReportSixSS($this->country,$this->filtered_data,$this->start_date,$this->end_date);	
       $sheets[] = new HHReportSixSOT($this->country,$this->start_date,$this->end_date);  
       return $sheets;
   }
}
