<?php

namespace App\Exports\Reports;

use DB;
use Auth;
use App\Country;
use App\Location;
use App\Household\Outlet;
use App\Household\Product;
use App\Household\Quotation;
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

class HHReportSixSS implements FromView, withColumnFormatting, WithEvents, WithTitle
{
	const FORMAT_ACCOUNTING    = '_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)';
	const FORMAT_ACCOUNTING_00 = '_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)';
    const FORMAT_PCODE = '00"."00"."00"."0"."00"."0';

     //parameters
   	public function __construct($country,$filtered_data,$start_date,$end_date)
   	{
   		$this->country = $country;
   		$this->filtered_data = $filtered_data;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
   	}

   //view
   	public function view(): View
   	{
   		$country     = Country::where('ccode',Auth::User()->country)->first();

   		return view('household.reports.six-ss', [
   			'country'           => $this->country,
   			'filtered_data'		=> $this->filtered_data,
            'start_date'        => $this->start_date,
            'end_date'          => $this->end_date,
   			'back'				=> ''
   		]);
   	}

    	//formt columns
    	public function columnFormats(): array
    	{
    		return [
    		   'A' => self::FORMAT_PCODE,
    		   'D' => self::FORMAT_ACCOUNTING_00,
    		   'E' => self::FORMAT_ACCOUNTING_00,
               'F' => self::FORMAT_ACCOUNTING,
    		   'G' => self::FORMAT_ACCOUNTING_00,
    		   'H' => self::FORMAT_ACCOUNTING_00,
    		   'I' => self::FORMAT_ACCOUNTING_00,
               'J' => self::FORMAT_ACCOUNTING_00,

    		   'L' => self::FORMAT_ACCOUNTING_00,
    		   'M' => self::FORMAT_ACCOUNTING,
    		   'N' => self::FORMAT_ACCOUNTING_00,
    		   'O' => self::FORMAT_ACCOUNTING_00,
    		   'P' => self::FORMAT_ACCOUNTING_00,
               'Q' => self::FORMAT_ACCOUNTING_00,

    		   'S' => self::FORMAT_ACCOUNTING_00,
    		   'T' => self::FORMAT_ACCOUNTING,
    		   'U' => self::FORMAT_ACCOUNTING_00,
    		   'V' => self::FORMAT_ACCOUNTING_00,
    		   'W' => self::FORMAT_ACCOUNTING_00,
               'X' => self::FORMAT_ACCOUNTING_00,

               'Z' => self::FORMAT_ACCOUNTING_00,
               'AA' => self::FORMAT_ACCOUNTING,
               'AB' => self::FORMAT_ACCOUNTING_00,
               'AC' => self::FORMAT_ACCOUNTING_00,
               'AD' => self::FORMAT_ACCOUNTING_00,
               'AE' => self::FORMAT_ACCOUNTING_00,
               'AF' => self::FORMAT_ACCOUNTING_00,
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
    	          $sheet->getColumnDimension('A')->setWidth(15);
    	          $sheet->getColumnDimension('B')->setWidth(32);
    	          $sheet->getColumnDimension('C')->setWidth(15);
    	          $sheet->getColumnDimension('D')->setWidth(15);
    	          $sheet->getColumnDimension('E')->setWidth(15);
    	          $sheet->getColumnDimension('F')->setWidth(15);
    	          $sheet->getColumnDimension('G')->setWidth(15);
    	          $sheet->getColumnDimension('H')->setWidth(15);
    	          $sheet->getColumnDimension('I')->setWidth(15);
                  $sheet->getColumnDimension('J')->setWidth(15);
    	          $sheet->getColumnDimension('K')->setWidth(2);
    	          $sheet->getColumnDimension('L')->setWidth(15);
    	          $sheet->getColumnDimension('M')->setWidth(15);
    	          $sheet->getColumnDimension('N')->setWidth(15);
    	          $sheet->getColumnDimension('O')->setWidth(15);
    	          $sheet->getColumnDimension('P')->setWidth(15);
                  $sheet->getColumnDimension('Q')->setWidth(15);
    	          $sheet->getColumnDimension('R')->setWidth(2);
    	          $sheet->getColumnDimension('S')->setWidth(15);
    	          $sheet->getColumnDimension('T')->setWidth(15);
    	          $sheet->getColumnDimension('U')->setWidth(15);
    	          $sheet->getColumnDimension('V')->setWidth(15);
    	          $sheet->getColumnDimension('W')->setWidth(15);
                  $sheet->getColumnDimension('X')->setWidth(15);
                  $sheet->getColumnDimension('Y')->setWidth(2);
                  $sheet->getColumnDimension('Z')->setWidth(15);
                  $sheet->getColumnDimension('AA')->setWidth(15);
                  $sheet->getColumnDimension('AB')->setWidth(15);
                  $sheet->getColumnDimension('AC')->setWidth(15);
                  $sheet->getColumnDimension('AD')->setWidth(15);
                  $sheet->getColumnDimension('AE')->setWidth(15);
    	          // $sheet->getColumnDimension('H')->setWidth(14);
    	          // $sheet->getPageSetup()->setFitToWidth(1);
    	          $sheet->freezePane('E8');
    	          // $style = array(
    	          //     'alignment' => array(
    	          //         'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    	          //         'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    	          //     )
    	          // );
    	          // $sheet->getStyle("A6:H7")->applyFromArray($style);
    	          $sheet->getStyle('A6:W7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
    	          $sheet->getStyle('E5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
    	          $sheet->getStyle('L5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
    	          $sheet->getStyle('S5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
                  $sheet->getStyle('Z5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
    	          // $sheet->getStyle('C2:D4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fac3c8');
    	      },
    	  ];
    	}

    	/**
    	* @return string
    	*/
    	public function title(): string
    	{
    		return 'Report 2a';
    	}
    	
}
