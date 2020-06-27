<?php

namespace App\Exports\Reports;

use Auth;
use App\Country;
use App\Location;
use App\Household\Outlet;
use App\Household\Quotation;
use App\Household\Product;
use App\Household\ProductClassification;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\WithTitle;

class HHReportThree implements FromView, withColumnFormatting, WithEvents, WithTitle
{
   	const FORMAT_ACCOUNTING = '_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)';
    const FORMAT_PCODE = '00"."00"."00"."0"."00"."0';
   
   	//parameters
    	public function __construct($country,$quotations_filtered,$quotations_totaled)
    	{
    		$this->country = $country;
    		$this->quotations_filtered = $quotations_filtered;
        $this->quotations_totaled = $quotations_totaled;
    	}

    	//view
    	public function view(): View
    	{
    		$country     = Country::where('ccode',Auth::User()->country)->first();
    		$locations   = Location::where('ccode',Auth::User()->country)->where('loclvl',2)->get();

    		// LOGG('SELECT','generated report 1.');
    		return view('household.reports.three', [
    			'country'         => $country,
    			'locations'        => $locations,
          'quotations_totaled' => $this->quotations_totaled,
    			'quotations_filtered' => $this->quotations_filtered,
               'back'            => ''
    		]);
    	}

   	//formt columns
   	public function columnFormats(): array
   	{
	    return [
	        'A' => self::FORMAT_PCODE,
          'C' => self::FORMAT_ACCOUNTING,
          'D' => self::FORMAT_ACCOUNTING,
          'E' => self::FORMAT_ACCOUNTING,
          'F' => self::FORMAT_ACCOUNTING,
          'G' => self::FORMAT_ACCOUNTING,
          'H' => self::FORMAT_ACCOUNTING,
          'I' => self::FORMAT_ACCOUNTING,
          'J' => self::FORMAT_ACCOUNTING,
          'K' => self::FORMAT_ACCOUNTING,
          'L' => self::FORMAT_ACCOUNTING,
          'M' => self::FORMAT_ACCOUNTING,
          'N' => self::FORMAT_ACCOUNTING,
          'O' => self::FORMAT_ACCOUNTING,
          'P' => self::FORMAT_ACCOUNTING,
          'Q' => self::FORMAT_ACCOUNTING,
          'R' => self::FORMAT_ACCOUNTING,
          'S' => self::FORMAT_ACCOUNTING,
          'T' => self::FORMAT_ACCOUNTING,
          'U' => self::FORMAT_ACCOUNTING,
          'V' => self::FORMAT_ACCOUNTING,
          'W' => self::FORMAT_ACCOUNTING,
          'X' => self::FORMAT_ACCOUNTING,
          'Y' => self::FORMAT_ACCOUNTING,
          'Z' => self::FORMAT_ACCOUNTING,
          'AA' => self::FORMAT_ACCOUNTING,
          'AB' => self::FORMAT_ACCOUNTING,
          'AC' => self::FORMAT_ACCOUNTING
	    ];
   	}

   	//format excel sheet
   	public function registerEvents(): array
   	{
		return [
		 AfterSheet::class => function(AfterSheet $event) {
		     $highestrow = $event->getSheet()->getHighestRow();
		     $highestcol = $event->getSheet()->getHighestColumn();
		     $sheet = $event->sheet;
		     $sheet->getColumnDimension('B')->setWidth(15);
		     $sheet->getColumnDimension('B')->setWidth(50);
		     $style = array(
		         'alignment' => array(
		             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
		             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
		         )
		     );
		     $sheet->getStyle("C6:".$highestcol."7")->applyFromArray($style);
		     $sheet->getStyle('A6:'.$highestcol.'7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
		 },
		];
   	}
    
    /**
    * @return string
    */
    public function title(): string
    {
      return 'Report 6';
    }   	
}
