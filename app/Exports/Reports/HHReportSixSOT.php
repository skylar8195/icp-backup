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

class HHReportSixSOT implements FromView, withColumnFormatting, WithEvents, WithTitle
{
	const FORMAT_ACCOUNTING    = '_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)';
	const FORMAT_ACCOUNTING_00 = '_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)';
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
        $products    = Product::orderBy('pcode','asc')->get('pcode','pname');
        
        $dbs = new \SQLite3('../database/production.sqlite');
        $dbs->loadExtension('libsqlitefunctions.dll');


    if ($this->start_date != null) {
        $stmt = $dbs->prepare('SELECT
                                p.pcode, p.pname, o.ocode,

                                case when COUNT(q.id)=0 then NULL else COUNT(q.id) end as quotations_oa,
                                sum(case when o.otype=1 then 1 else NULL end) as quotations_o1,
                                sum(case when o.otype=2 then 1 else NULL end) as quotations_o2,
                                sum(case when o.otype=3 then 1 else NULL end) as quotations_o3,
                                sum(case when o.otype=4 then 1 else NULL end) as quotations_o4,
                                sum(case when o.otype=5 then 1 else NULL end) as quotations_o5,
                                sum(case when o.otype=6 then 1 else NULL end) as quotations_o6,
                                sum(case when o.otype=7 then 1 else NULL end) as quotations_o7,
                                sum(case when o.otype=8 then 1 else NULL end) as quotations_o8,
                                sum(case when o.otype=9 then 1 else NULL end) as quotations_o9,

                                avg(q.con_price ) as avgprice_oa,
                                ifnull((sum(case when o.otype=1 then con_price else 0 end))/(sum(case when o.otype=1 then 1 else 0 end)),NULL) as avgprice_o1,
                                ifnull((sum(case when o.otype=2 then con_price else 0 end))/(sum(case when o.otype=2 then 1 else 0 end)),NULL) as avgprice_o2,
                                ifnull((sum(case when o.otype=3 then con_price else 0 end))/(sum(case when o.otype=3 then 1 else 0 end)),NULL) as avgprice_o3,
                                ifnull((sum(case when o.otype=4 then con_price else 0 end))/(sum(case when o.otype=4 then 1 else 0 end)),NULL) as avgprice_o4,
                                ifnull((sum(case when o.otype=5 then con_price else 0 end))/(sum(case when o.otype=5 then 1 else 0 end)),NULL) as avgprice_o5,
                                ifnull((sum(case when o.otype=6 then con_price else 0 end))/(sum(case when o.otype=6 then 1 else 0 end)),NULL) as avgprice_o6,
                                ifnull((sum(case when o.otype=7 then con_price else 0 end))/(sum(case when o.otype=7 then 1 else 0 end)),NULL) as avgprice_o7,
                                ifnull((sum(case when o.otype=8 then con_price else 0 end))/(sum(case when o.otype=8 then 1 else 0 end)),NULL) as avgprice_o8,
                                ifnull((sum(case when o.otype=9 then con_price else 0 end))/(sum(case when o.otype=9 then 1 else 0 end)),NULL) as avgprice_o9,

stdev(con_price)/avg(q.con_price ) as cv_oa,
(stdev(case when o.otype=1 then q.con_price else NULL end)/ifnull((sum(case when o.otype=1 then con_price else 0 end))/(sum(case when o.otype=1 then 1 else 0 end)),"0"))*100 as cv_o1,
(stdev(case when o.otype=2 then q.con_price else NULL end)/ifnull((sum(case when o.otype=2 then con_price else 0 end))/(sum(case when o.otype=2 then 1 else 0 end)),"0"))*100 as cv_o2,
(stdev(case when o.otype=3 then q.con_price else NULL end)/ifnull((sum(case when o.otype=3 then con_price else 0 end))/(sum(case when o.otype=3 then 1 else 0 end)),"0"))*100 as cv_o3,
(stdev(case when o.otype=4 then q.con_price else NULL end)/ifnull((sum(case when o.otype=4 then con_price else 0 end))/(sum(case when o.otype=4 then 1 else 0 end)),"0"))*100 as cv_o4,
(stdev(case when o.otype=5 then q.con_price else NULL end)/ifnull((sum(case when o.otype=5 then con_price else 0 end))/(sum(case when o.otype=5 then 1 else 0 end)),"0"))*100 as cv_o5,
(stdev(case when o.otype=6 then q.con_price else NULL end)/ifnull((sum(case when o.otype=6 then con_price else 0 end))/(sum(case when o.otype=6 then 1 else 0 end)),"0"))*100 as cv_o6,
(stdev(case when o.otype=7 then q.con_price else NULL end)/ifnull((sum(case when o.otype=7 then con_price else 0 end))/(sum(case when o.otype=7 then 1 else 0 end)),"0"))*100 as cv_o7,
(stdev(case when o.otype=8 then q.con_price else NULL end)/ifnull((sum(case when o.otype=8 then con_price else 0 end))/(sum(case when o.otype=8 then 1 else 0 end)),"0"))*100 as cv_o8,
(stdev(case when o.otype=9 then q.con_price else NULL end)/ifnull((sum(case when o.otype=9 then con_price else 0 end))/(sum(case when o.otype=9 then 1 else 0 end)),"0"))*100 as cv_o9,

                                max(q.con_price) as max_oa,
                                max(case when o.otype=1 then q.con_price else NULL end) as max_o1,
                                max(case when o.otype=2 then q.con_price else NULL end) as max_o2,
                                max(case when o.otype=3 then q.con_price else NULL end) as max_o3,
                                max(case when o.otype=4 then q.con_price else NULL end) as max_o4,
                                max(case when o.otype=5 then q.con_price else NULL end) as max_o5,
                                max(case when o.otype=6 then q.con_price else NULL end) as max_o6,
                                max(case when o.otype=7 then q.con_price else NULL end) as max_o7,
                                max(case when o.otype=8 then q.con_price else NULL end) as max_o8,
                                max(case when o.otype=9 then q.con_price else NULL end) as max_o9,

                                min(q.con_price) as min_oa,
                                min(case when o.otype=1 then q.con_price else NULL end) as min_o1,
                                min(case when o.otype=2 then q.con_price else NULL end) as min_o2,
                                min(case when o.otype=3 then q.con_price else NULL end) as min_o3,
                                min(case when o.otype=4 then q.con_price else NULL end) as min_o4,
                                min(case when o.otype=5 then q.con_price else NULL end) as min_o5,
                                min(case when o.otype=6 then q.con_price else NULL end) as min_o6,
                                min(case when o.otype=7 then q.con_price else NULL end) as min_o7,
                                min(case when o.otype=8 then q.con_price else NULL end) as min_o8,
                                min(case when o.otype=9 then q.con_price else NULL end) as min_o9,

                                MIN(con_price)/MAX(con_price) as mmr_oa,
                                min(case when o.otype=1 then q.con_price else NULL end)/max(case when o.otype=1 then q.con_price else NULL end) as mmr_o1,
                                min(case when o.otype=2 then q.con_price else NULL end)/max(case when o.otype=2 then q.con_price else NULL end) as mmr_o2,
                                min(case when o.otype=3 then q.con_price else NULL end)/max(case when o.otype=3 then q.con_price else NULL end) as mmr_o3,
                                min(case when o.otype=4 then q.con_price else NULL end)/max(case when o.otype=4 then q.con_price else NULL end) as mmr_o4,
                                min(case when o.otype=5 then q.con_price else NULL end)/max(case when o.otype=5 then q.con_price else NULL end) as mmr_o5,
                                min(case when o.otype=6 then q.con_price else NULL end)/max(case when o.otype=6 then q.con_price else NULL end) as mmr_o6,
                                min(case when o.otype=7 then q.con_price else NULL end)/max(case when o.otype=7 then q.con_price else NULL end) as mmr_o7,
                                min(case when o.otype=8 then q.con_price else NULL end)/max(case when o.otype=8 then q.con_price else NULL end) as mmr_o8,
                                min(case when o.otype=9 then q.con_price else NULL end)/max(case when o.otype=9 then q.con_price else NULL end) as mmr_o9

                                FROM household_products p
                                left join household_quotations q on p.pcode = q.pcode
                                left join household_outlets o on q.oid = o.id
                                where q.obv_date between :startdate and :enddate
                                and o.ccode = :ccode
                                group by p.pcode');
    $stmt->bindValue(':startdate',$this->start_date);
    $stmt->bindValue(':enddate',$this->end_date);
    $stmt->bindValue(':ccode',Auth::User()->country);
    $results = $stmt->execute();

    } else {
        $results = $dbs->query('SELECT
                                p.pcode, p.pname, o.ocode,

                                case when COUNT(q.id)=0 then NULL else COUNT(q.id) end as quotations_oa,
                                sum(case when o.otype=1 then 1 else NULL end) as quotations_o1,
                                sum(case when o.otype=2 then 1 else NULL end) as quotations_o2,
                                sum(case when o.otype=3 then 1 else NULL end) as quotations_o3,
                                sum(case when o.otype=4 then 1 else NULL end) as quotations_o4,
                                sum(case when o.otype=5 then 1 else NULL end) as quotations_o5,
                                sum(case when o.otype=6 then 1 else NULL end) as quotations_o6,
                                sum(case when o.otype=7 then 1 else NULL end) as quotations_o7,
                                sum(case when o.otype=8 then 1 else NULL end) as quotations_o8,
                                sum(case when o.otype=9 then 1 else NULL end) as quotations_o9,

                                avg(q.con_price ) as avgprice_oa,
                                ifnull((sum(case when o.otype=1 then con_price else 0 end))/(sum(case when o.otype=1 then 1 else 0 end)),NULL) as avgprice_o1,
                                ifnull((sum(case when o.otype=2 then con_price else 0 end))/(sum(case when o.otype=2 then 1 else 0 end)),NULL) as avgprice_o2,
                                ifnull((sum(case when o.otype=3 then con_price else 0 end))/(sum(case when o.otype=3 then 1 else 0 end)),NULL) as avgprice_o3,
                                ifnull((sum(case when o.otype=4 then con_price else 0 end))/(sum(case when o.otype=4 then 1 else 0 end)),NULL) as avgprice_o4,
                                ifnull((sum(case when o.otype=5 then con_price else 0 end))/(sum(case when o.otype=5 then 1 else 0 end)),NULL) as avgprice_o5,
                                ifnull((sum(case when o.otype=6 then con_price else 0 end))/(sum(case when o.otype=6 then 1 else 0 end)),NULL) as avgprice_o6,
                                ifnull((sum(case when o.otype=7 then con_price else 0 end))/(sum(case when o.otype=7 then 1 else 0 end)),NULL) as avgprice_o7,
                                ifnull((sum(case when o.otype=8 then con_price else 0 end))/(sum(case when o.otype=8 then 1 else 0 end)),NULL) as avgprice_o8,
                                ifnull((sum(case when o.otype=9 then con_price else 0 end))/(sum(case when o.otype=9 then 1 else 0 end)),NULL) as avgprice_o9,

stdev(con_price)/avg(q.con_price ) as cv_oa,
(stdev(case when o.otype=1 then q.con_price else NULL end)/ifnull((sum(case when o.otype=1 then con_price else 0 end))/(sum(case when o.otype=1 then 1 else 0 end)),"0"))*100 as cv_o1,
(stdev(case when o.otype=2 then q.con_price else NULL end)/ifnull((sum(case when o.otype=2 then con_price else 0 end))/(sum(case when o.otype=2 then 1 else 0 end)),"0"))*100 as cv_o2,
(stdev(case when o.otype=3 then q.con_price else NULL end)/ifnull((sum(case when o.otype=3 then con_price else 0 end))/(sum(case when o.otype=3 then 1 else 0 end)),"0"))*100 as cv_o3,
(stdev(case when o.otype=4 then q.con_price else NULL end)/ifnull((sum(case when o.otype=4 then con_price else 0 end))/(sum(case when o.otype=4 then 1 else 0 end)),"0"))*100 as cv_o4,
(stdev(case when o.otype=5 then q.con_price else NULL end)/ifnull((sum(case when o.otype=5 then con_price else 0 end))/(sum(case when o.otype=5 then 1 else 0 end)),"0"))*100 as cv_o5,
(stdev(case when o.otype=6 then q.con_price else NULL end)/ifnull((sum(case when o.otype=6 then con_price else 0 end))/(sum(case when o.otype=6 then 1 else 0 end)),"0"))*100 as cv_o6,
(stdev(case when o.otype=7 then q.con_price else NULL end)/ifnull((sum(case when o.otype=7 then con_price else 0 end))/(sum(case when o.otype=7 then 1 else 0 end)),"0"))*100 as cv_o7,
(stdev(case when o.otype=8 then q.con_price else NULL end)/ifnull((sum(case when o.otype=8 then con_price else 0 end))/(sum(case when o.otype=8 then 1 else 0 end)),"0"))*100 as cv_o8,
(stdev(case when o.otype=9 then q.con_price else NULL end)/ifnull((sum(case when o.otype=9 then con_price else 0 end))/(sum(case when o.otype=9 then 1 else 0 end)),"0"))*100 as cv_o9,

                                max(q.con_price) as max_oa,
                                max(case when o.otype=1 then q.con_price else NULL end) as max_o1,
                                max(case when o.otype=2 then q.con_price else NULL end) as max_o2,
                                max(case when o.otype=3 then q.con_price else NULL end) as max_o3,
                                max(case when o.otype=4 then q.con_price else NULL end) as max_o4,
                                max(case when o.otype=5 then q.con_price else NULL end) as max_o5,
                                max(case when o.otype=6 then q.con_price else NULL end) as max_o6,
                                max(case when o.otype=7 then q.con_price else NULL end) as max_o7,
                                max(case when o.otype=8 then q.con_price else NULL end) as max_o8,
                                max(case when o.otype=9 then q.con_price else NULL end) as max_o9,

                                min(q.con_price) as min_oa,
                                min(case when o.otype=1 then q.con_price else NULL end) as min_o1,
                                min(case when o.otype=2 then q.con_price else NULL end) as min_o2,
                                min(case when o.otype=3 then q.con_price else NULL end) as min_o3,
                                min(case when o.otype=4 then q.con_price else NULL end) as min_o4,
                                min(case when o.otype=5 then q.con_price else NULL end) as min_o5,
                                min(case when o.otype=6 then q.con_price else NULL end) as min_o6,
                                min(case when o.otype=7 then q.con_price else NULL end) as min_o7,
                                min(case when o.otype=8 then q.con_price else NULL end) as min_o8,
                                min(case when o.otype=9 then q.con_price else NULL end) as min_o9,

                                MIN(con_price)/MAX(con_price) as mmr_oa,
                                min(case when o.otype=1 then q.con_price else NULL end)/max(case when o.otype=1 then q.con_price else NULL end) as mmr_o1,
                                min(case when o.otype=2 then q.con_price else NULL end)/max(case when o.otype=2 then q.con_price else NULL end) as mmr_o2,
                                min(case when o.otype=3 then q.con_price else NULL end)/max(case when o.otype=3 then q.con_price else NULL end) as mmr_o3,
                                min(case when o.otype=4 then q.con_price else NULL end)/max(case when o.otype=4 then q.con_price else NULL end) as mmr_o4,
                                min(case when o.otype=5 then q.con_price else NULL end)/max(case when o.otype=5 then q.con_price else NULL end) as mmr_o5,
                                min(case when o.otype=6 then q.con_price else NULL end)/max(case when o.otype=6 then q.con_price else NULL end) as mmr_o6,
                                min(case when o.otype=7 then q.con_price else NULL end)/max(case when o.otype=7 then q.con_price else NULL end) as mmr_o7,
                                min(case when o.otype=8 then q.con_price else NULL end)/max(case when o.otype=8 then q.con_price else NULL end) as mmr_o8,
                                min(case when o.otype=9 then q.con_price else NULL end)/max(case when o.otype=9 then q.con_price else NULL end) as mmr_o9

                                FROM household_products p
                                left join household_quotations q on p.pcode = q.pcode
                                left join household_outlets o on q.oid = o.id
                                group by p.pcode');
    }
        $filtered_products = collect();
        while ($row = $results->fetchArray()) {
            $filtered_products->add($row);
        }


   		return view('household.reports.six-sot', [
   			'country'         		=> $this->country,
   			'filtered_products'		=> $filtered_products,
            'start_date'            => $this->start_date,
            'end_date'              => $this->end_date,
   			'back'				    => ''
   		]);
   	}

    	//formt columns
    	public function columnFormats(): array
    	{
    		return [
    		   'A' => self::FORMAT_PCODE,
    		   'C' => self::FORMAT_ACCOUNTING_00,
    		   'D' => self::FORMAT_ACCOUNTING_00,
    		   'E' => self::FORMAT_ACCOUNTING_00,
    		   'F' => self::FORMAT_ACCOUNTING_00,
    		   'G' => self::FORMAT_ACCOUNTING_00,
    		   'H' => self::FORMAT_ACCOUNTING_00,
    		   'I' => self::FORMAT_ACCOUNTING_00,
    		   'J' => self::FORMAT_ACCOUNTING_00,
    		   'K' => self::FORMAT_ACCOUNTING_00,
    		   'L' => self::FORMAT_ACCOUNTING_00,
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
    	          $sheet->getColumnDimension('A')->setWidth(18);
    	          $sheet->getColumnDimension('B')->setWidth(30);
    	          $sheet->getColumnDimension('C')->setWidth(15);
    	          $sheet->getColumnDimension('D')->setWidth(15);
    	          $sheet->getColumnDimension('E')->setWidth(15);
    	          $sheet->getColumnDimension('F')->setWidth(15);
    	          $sheet->getColumnDimension('G')->setWidth(15);
    	          $sheet->getColumnDimension('H')->setWidth(15);
    	          $sheet->getColumnDimension('I')->setWidth(15);
    	          $sheet->getColumnDimension('J')->setWidth(15);
    	          $sheet->getColumnDimension('K')->setWidth(15);
    	          $sheet->getColumnDimension('L')->setWidth(15);
    	          // $sheet->getColumnDimension('H')->setWidth(14);
    	          // $sheet->getPageSetup()->setFitToWidth(1);
    	          $sheet->freezePane('M8');
    	          $style = array(
    	              'alignment' => array(
    	                  'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    	                  'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    	              )
    	          );
    	          $sheet->getStyle("A8:A".$highestrow)->applyFromArray($style);
    	          $sheet->getStyle("A6:L7")->applyFromArray($style);
    	          $sheet->getStyle('A6:L7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');


                  $event->sheet->getStyle('D7:L7')->getNumberFormat()->setFormatCode('0');

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
    		return 'Report 2b';
    	}
}
