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

class HHReportFive implements FromView, withColumnFormatting, WithEvents, WithTitle
{
    const FORMAT_ACCOUNTING = '_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)';
    const FORMAT_PCODE = '00"."00"."00"."0"."00"."0';
     
     //parameters
	public function __construct($country,$start_date,$end_date)
	{
		$this->country = $country;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
	}

	//view
	public function view(): View
	{
		$country     = Country::where('ccode',Auth::User()->country)->first();
    	$products    = Product::orderBy('pcode','asc')->get();

            if ($this->start_date != null) {
			         $quotations_totaled = DB::table('household_products')
                        ->leftJoin('household_quotations', 'household_quotations.pcode', '=', 'household_products.pcode')
                        ->leftJoin('household_outlets', 'household_quotations.oid', '=', 'household_outlets.id')
                        ->select(
                            DB::raw('case when COUNT(household_quotations.id)=0 then 0 else COUNT(household_quotations.id) end as total_quotes'),
                            DB::raw('sum(case when household_outlets.otype=1 then 1 else 0 end) as total_quotes_1'),
                            DB::raw('sum(case when household_outlets.otype=2 then 1 else 0 end) as total_quotes_2'),
                            DB::raw('sum(case when household_outlets.otype=3 then 1 else 0 end) as total_quotes_3'),
                            DB::raw('sum(case when household_outlets.otype=4 then 1 else 0 end) as total_quotes_4'),
                            DB::raw('sum(case when household_outlets.otype=5 then 1 else 0 end) as total_quotes_5'),
                            DB::raw('sum(case when household_outlets.otype=6 then 1 else 0 end) as total_quotes_6'),
                            DB::raw('sum(case when household_outlets.otype=7 then 1 else 0 end) as total_quotes_7'),
                            DB::raw('sum(case when household_outlets.otype=8 then 1 else 0 end) as total_quotes_8'),
                            DB::raw('sum(case when household_outlets.otype=9 then 1 else 0 end) as total_quotes_9')
                        )
                        ->whereBetween('household_quotations.obv_date',array($this->start_date, $this->end_date))
                        ->get();
                     $quotations_filtered = DB::table('household_products')
			  		    ->leftJoin('household_quotations', 'household_quotations.pcode', '=', 'household_products.pcode')
                    	->leftJoin('household_outlets', 'household_quotations.oid', '=', 'household_outlets.id')
                    	->select(
                    		'household_products.pcode',
                    		'household_products.pname',
                    		DB::raw('case when COUNT(household_quotations.id)=0 then 0 else COUNT(household_quotations.id) end as total_quotes'),
                    		DB::raw('sum(case when household_outlets.otype=1 then 1 else 0 end) as total_quotes_1'),
                    		DB::raw('sum(case when household_outlets.otype=2 then 1 else 0 end) as total_quotes_2'),
                    		DB::raw('sum(case when household_outlets.otype=3 then 1 else 0 end) as total_quotes_3'),
                    		DB::raw('sum(case when household_outlets.otype=4 then 1 else 0 end) as total_quotes_4'),
                    		DB::raw('sum(case when household_outlets.otype=5 then 1 else 0 end) as total_quotes_5'),
                    		DB::raw('sum(case when household_outlets.otype=6 then 1 else 0 end) as total_quotes_6'),
                    		DB::raw('sum(case when household_outlets.otype=7 then 1 else 0 end) as total_quotes_7'),
                    		DB::raw('sum(case when household_outlets.otype=8 then 1 else 0 end) as total_quotes_8'),
                    		DB::raw('sum(case when household_outlets.otype=9 then 1 else 0 end) as total_quotes_9')
                    	)
                        ->whereBetween('household_quotations.obv_date',array($this->start_date, $this->end_date))
                        ->groupBy('household_products.pcode')->get();
            } else {
                     $quotations_totaled = DB::table('household_products')
                        ->leftJoin('household_quotations', 'household_quotations.pcode', '=', 'household_products.pcode')
                        ->leftJoin('household_outlets', 'household_quotations.oid', '=', 'household_outlets.id')
                        ->select(
                            DB::raw('case when COUNT(household_quotations.id)=0 then 0 else COUNT(household_quotations.id) end as total_quotes'),
                            DB::raw('sum(case when household_outlets.otype=1 then 1 else 0 end) as total_quotes_1'),
                            DB::raw('sum(case when household_outlets.otype=2 then 1 else 0 end) as total_quotes_2'),
                            DB::raw('sum(case when household_outlets.otype=3 then 1 else 0 end) as total_quotes_3'),
                            DB::raw('sum(case when household_outlets.otype=4 then 1 else 0 end) as total_quotes_4'),
                            DB::raw('sum(case when household_outlets.otype=5 then 1 else 0 end) as total_quotes_5'),
                            DB::raw('sum(case when household_outlets.otype=6 then 1 else 0 end) as total_quotes_6'),
                            DB::raw('sum(case when household_outlets.otype=7 then 1 else 0 end) as total_quotes_7'),
                            DB::raw('sum(case when household_outlets.otype=8 then 1 else 0 end) as total_quotes_8'),
                            DB::raw('sum(case when household_outlets.otype=9 then 1 else 0 end) as total_quotes_9')
                        )->get();
                     $quotations_filtered = DB::table('household_products')
                        ->leftJoin('household_quotations', 'household_quotations.pcode', '=', 'household_products.pcode')
                        ->leftJoin('household_outlets', 'household_quotations.oid', '=', 'household_outlets.id')
                        ->select(
                            'household_products.pcode',
                            'household_products.pname',
                            DB::raw('case when COUNT(household_quotations.id)=0 then 0 else COUNT(household_quotations.id) end as total_quotes'),
                            DB::raw('sum(case when household_outlets.otype=1 then 1 else 0 end) as total_quotes_1'),
                            DB::raw('sum(case when household_outlets.otype=2 then 1 else 0 end) as total_quotes_2'),
                            DB::raw('sum(case when household_outlets.otype=3 then 1 else 0 end) as total_quotes_3'),
                            DB::raw('sum(case when household_outlets.otype=4 then 1 else 0 end) as total_quotes_4'),
                            DB::raw('sum(case when household_outlets.otype=5 then 1 else 0 end) as total_quotes_5'),
                            DB::raw('sum(case when household_outlets.otype=6 then 1 else 0 end) as total_quotes_6'),
                            DB::raw('sum(case when household_outlets.otype=7 then 1 else 0 end) as total_quotes_7'),
                            DB::raw('sum(case when household_outlets.otype=8 then 1 else 0 end) as total_quotes_8'),
                            DB::raw('sum(case when household_outlets.otype=9 then 1 else 0 end) as total_quotes_9')
                        )
                        ->groupBy('household_products.pcode')->get();
            }
		LOGG('SELECT','generated report 5.');
		return view('household.reports.five', [
			'country'         		=> $this->country,
			'products'			=> $products,
            'quotations_totaled'   => $quotations_totaled,
			'quotations_filtered'	=> $quotations_filtered,
			'back'				=> ''
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
         'L' => self::FORMAT_ACCOUNTING
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
                $sheet->getColumnDimension('C')->setWidth(16);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->freezePane('M8');
                $style = array(
                    'alignment' => array(
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    )
                );
                $sheet->getStyle("A6:L7")->applyFromArray($style);
                $sheet->getStyle('A6:L7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
            },
        ];
    }

    /**
    * @return string
    */
    public function title(): string
    {
        return 'Report 5';
    }
}
