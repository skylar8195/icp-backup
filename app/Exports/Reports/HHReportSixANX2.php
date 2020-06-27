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

class HHReportSixANX2 implements FromView, withColumnFormatting, WithEvents, WithTitle
{
    const FORMAT_ACCOUNTING    = '_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)';
    const FORMAT_ACCOUNTING_00 = '_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)';
    const FORMAT_PCODE = '00"."00"."00"."0"."00"."0';
    
    //parameters
   	public function __construct($country,$quotations)
   	{
   		$this->country = $country;
      $this->quotations = $quotations;
   	}

   //view
   	public function view(): View
   	{
   		$country     = $this->country;
      $filtered_quotations = collect($this->quotations)->map(function ($q) use ($country) {
              $outletdata = DB::table('household_quotations')
                            ->select(DB::raw("(household_outlets.ccode || '' || lvl2 || '' || lvl3 || '' || lvl4 || '' || lvl5 || '' || household_outlets.ocode) as loclvl"),'household_outlets.oname','obv_date','obv_qty','price','con_price','remarks')
                            ->leftJoin('household_outlets','household_outlets.id','=','household_quotations.oid')
                            ->where('pcode',$q['pcode'])->orderBy('household_quotations.con_price','asc')->get();
              return [
                  'pcode'         => $q['pcode']." ",
                  'pname'         => $q['pname'],
                  'avgprice'      => number_format((float)$q['avg'],2),
                  'quotations'    => (float)$q['quotes'],
                  'cv'            => (float)$q['cv'],
                  'stdev'         => (float)$q['stdev'],
                  'min'           => number_format((float)$q['min'],2),
                  'max'           => number_format((float)$q['max'],2),
                  'mmr'           => (float)$q['mmr'],
                  'lowerlimit'    => (float)($q['lowerlimit']),
                  'upperlimit'    => (float)($q['upperlimit']),
                  'outletdata'    => $outletdata
              ];
      });

   		LOGG('SELECT','generated report 6.');
   		return view('household.reports.six-anx2', [
   			'country'         		=> $this->country,
        'filtered_quotations' => $filtered_quotations,
   			'back'				=> ''
   		]);
   	}

    	//format columns
    	public function columnFormats(): array
    	{
    		return [
    		   'A' => NumberFormat::FORMAT_NUMBER,
           'C' => NumberFormat::FORMAT_TEXT,
           'D' => NumberFormat::FORMAT_TEXT,
           'E' => self::FORMAT_ACCOUNTING_00,
           'F' => self::FORMAT_ACCOUNTING_00,
           'G' => self::FORMAT_ACCOUNTING_00,
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
    	          $sheet->getColumnDimension('A')->setWidth(21);
    	          $sheet->getColumnDimension('B')->setWidth(16);
    	          $sheet->getColumnDimension('C')->setWidth(25);
                $sheet->getColumnDimension('D')->setWidth(16);
                $sheet->getColumnDimension('E')->setWidth(17);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(18);
                $sheet->getColumnDimension('H')->setWidth(18);
    	          // $sheet->getColumnDimension('H')->setWidth(14);
    	          // $sheet->getPageSetup()->setFitToWidth(1);
    	          $sheet->freezePane('I7');


    	          // $style = array(
    	          //     'alignment' => array(
    	          //         'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    	          //         'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    	          //     )
    	          // );
    	          // $sheet->getStyle("A6:H7")->applyFromArray($style);
    	          // $sheet->getStyle('A10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
    	          // $sheet->getStyle('A19')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
    	          // $sheet->getStyle('A23')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
    	          // $sheet->getStyle('A34')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
    	          // $sheet->getStyle('C2:D4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fac3c8');
    	      },
    	  ];
    	}

    	/**
    	* @return string
    	*/
    	public function title(): string
    	{
    		return 'Annex2';
    	}
}
