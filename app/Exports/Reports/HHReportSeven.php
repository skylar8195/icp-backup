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

class HHReportSeven implements FromView, withColumnFormatting, WithEvents
{
    const FORMAT_ACCOUNTING = '_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)';
    const FORMAT_ACCOUNTING_DECIMAL = '_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)';
    const FORMAT_PCODE = '00"."00"."00"."0"."00"."0';
         
     //parameters
    	public function __construct($country)
    	{
    		$this->country = $country;
    	}

    	//view
    	public function view(): View
    	{
    		$country     = Country::where('ccode',Auth::User()->country)->first();
   		$products    = Product::orderBy('pcode','asc')->get();

   		$filtered_quotations = $products->map(function ($q) {
				$quotation  = Quotation::with('product')->with('outlet')
			                    ->select(
			                        'pcode',
			                        'ocode',
			                        DB::raw('AVG(con_price)'),
			                        DB::raw('COUNT(id)'),
			                        DB::raw('MIN(con_price)'),
			                        DB::raw('MAX(con_price)'),
			                        DB::raw('MIN(con_price)/MAX(con_price)'),
			                        DB::raw('stdev(con_price)/AVG(con_price)*100')
			                    )->where('pcode',$q->pcode)
			                     ->where('ccode',$country->ccode)->first();
			     return [
			         'pcode'         => $q->pcode,
			         'pname'         => $q->pname,
			         'avgprice'      => (float)$quotation['AVG(con_price)'],
			         'quotations'    => (float)$quotation['COUNT(id)'],
			         'cv'            => (float)$quotation['stdev(con_price)/AVG(con_price)*100'],
			         'min'           => (float)$quotation['MIN(con_price)'],
			         'max'           => (float)$quotation['MAX(con_price)'],
			         'mmr'           => (float)$quotation['MIN(con_price)/MAX(con_price)']
			     ];
   		});

    		LOGG('SELECT','generated report 7.');
    		return view('household.reports.seven', [
    			'country'         		=> $this->country,
    			'products'			=> $products,
    			'filtered_quotations'	=> $filtered_quotations,
    			'back'				=> ''
    		]);
    	}

	//formt columns
	public function columnFormats(): array
	{
		return [
		   'A' => self::FORMAT_PCODE,
		   'C' => self::FORMAT_ACCOUNTING_DECIMAL,
		   'D' => self::FORMAT_ACCOUNTING,
		   'E' => self::FORMAT_ACCOUNTING_DECIMAL,
		   'F' => self::FORMAT_ACCOUNTING_DECIMAL,
		   'G' => self::FORMAT_ACCOUNTING_DECIMAL,
		   'H' => self::FORMAT_ACCOUNTING_DECIMAL
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
	          $sheet->getColumnDimension('B')->setWidth(40);
	          $sheet->getColumnDimension('C')->setWidth(14);
	          $sheet->getColumnDimension('H')->setWidth(14);
	          $sheet->getPageSetup()->setFitToWidth(1);
	          $sheet->freezePane('I8');
	          $style = array(
	              'alignment' => array(
	                  'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
	                  'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
	              )
	          );
	          $sheet->getStyle("A6:H6")->applyFromArray($style);
	          $sheet->getStyle('A6:H7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
	          $sheet->getStyle('C2:D4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fac3c8');
	      },
	  ];
	}
}
