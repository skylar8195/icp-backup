<?php

namespace App\Exports\Reports;

use Auth;
use App\Country;
use App\Exports\Reports\HHReportSixANX1;
use App\Exports\Reports\HHReportSixANX2;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HHReportNine implements WithMultipleSheets
{

	//parameters
   	public function __construct($country,$quotes)
   	{
   		$this->country = $country;
      	$this->quotes = $quotes;
   	}

	/**
	* @return array
	*/
	public function sheets(): array
	{
	  $sheets = [];
	  $sheets[] = new HHReportSSLoc($this->country,$this->quotes,"Average");
	  $sheets[] = new HHReportSSLoc($this->country,$this->quotes,"Quotations");
	  $sheets[] = new HHReportSSLoc($this->country,$this->quotes,"CV");
	  $sheets[] = new HHReportSSLoc($this->country,$this->quotes,"Min");
	  $sheets[] = new HHReportSSLoc($this->country,$this->quotes,"Max");
	  $sheets[] = new HHReportSSLoc($this->country,$this->quotes,"MinMaxRatio");
	  return $sheets;
	}
}
