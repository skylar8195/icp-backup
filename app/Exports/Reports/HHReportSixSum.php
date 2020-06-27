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

class HHReportSixSum implements FromView, withColumnFormatting, WithEvents, WithTitle
{
	//parameters
    	public function __construct($country,$products,$start_date,$end_date)
    	{
    		$this->country = $country;
    		$this->products = $products;
    		$this->start_date = $start_date;
		$this->end_date = $end_date;
    	}

    //view
    	public function view(): View
    	{
    		$country     = DB::table('lib_countries')->where('ccode',Auth::User()->country)->first();
   		$products    = $this->products;
   		$prod_avail  = DB::table('household_products')->where('avail',1)->orWhere('avail',2)->count();

   		$dbs = new \SQLite3('../database/production.sqlite');
        	$dbs->loadExtension('libsqlitefunctions.dll');

   		if ($this->start_date) {
   			$quotations  = DB::table('household_quotations')
   							->whereBetween('obv_date',array($this->start_date, $this->end_date))
   							->get();

$prodgreater3 = Product::has('quotations','>','2')->whereBetween('obv_date',array($this->start_date, $this->end_date))->count();
$prodgreater5 = Product::has('quotations','>','5')->whereBetween('obv_date',array($this->start_date, $this->end_date))->count();

$prodgreater6 = Product::has('quotations','>','5')->whereBetween('obv_date',array($this->start_date, $this->end_date))->count();
$prodgreater10 = Product::has('quotations','>','10')->whereBetween('obv_date',array($this->start_date, $this->end_date))->count();

$prodgreater11 = Product::has('quotations','>','10')->whereBetween('obv_date',array($this->start_date, $this->end_date))->count();
$prodgreater14 = Product::has('quotations','>','14')->whereBetween('obv_date',array($this->start_date, $this->end_date))->count();

$prodgreater15 = Product::has('quotations','>','14')->whereBetween('obv_date',array($this->start_date, $this->end_date))->count();
$prodgreater30 = Product::has('quotations','>','30')->whereBetween('obv_date',array($this->start_date, $this->end_date))->count();

$prodgreater30 = Product::has('quotations','>','29')->whereBetween('obv_date',array($this->start_date, $this->end_date))->count();
$prodgreater90 = Product::has('quotations','>','90')->whereBetween('obv_date',array($this->start_date, $this->end_date))->count();


			$stmt = $dbs->prepare('SELECT stdev(con_price)/avg(con_price)*100, MIN(con_price)/MAX(con_price)
        						FROM household_quotations q
                                	left join household_products p on p.pcode = q.pcode
                                	where q.obv_date between :startdate and :enddate
                                	group by p.pcode');
			$stmt->bindValue(':startdate',$this->start_date);
		     $stmt->bindValue(':enddate',$this->end_date);
		     $results = $stmt->execute();
	          $qcv = collect();
	        while ($row = $results->fetchArray()) {
	            $qcv->add($row);
	        }
   		} else {
   			$quotations  = DB::table('household_quotations')->get();
   			
	   		$prodgreater3 = Product::has('quotations', '>', '2')->count();
	   		$prodgreater5 = Product::has('quotations', '>', '5')->count();

	   		$prodgreater6 = Product::has('quotations', '>', '5')->count();
	   		$prodgreater10 = Product::has('quotations', '>', '10')->count();

	   		$prodgreater11 = Product::has('quotations', '>', '10')->count();
	   		$prodgreater14 = Product::has('quotations', '>', '14')->count();

	   		$prodgreater15 = Product::has('quotations', '>', '14')->count();
	   		$prodgreater30 = Product::has('quotations', '>', '30')->count();
	   		$prodgreater90 = Product::has('quotations', '>', '90')->count();
	   		
        		$results = $dbs->query('SELECT MIN(con_price)/MAX(con_price), stdev(con_price)/avg(con_price)*100
        							FROM household_quotations q
                                		left join household_products p on p.pcode = q.pcode
                                		group by p.pcode');
	        	$qcv = collect();
	        	while ($row = $results->fetchArray()) {
				$qcv->add($row);
	        	}
   		}

   		$prod_priced = $quotations->groupBy('pcode')->count();

          $cv1 = 0;
          $cv2 = 0;
          $cv3 = 0;
          $cv4 = 0;
          $cv5 = 0;
          $cv6 = 0;
          $cv7 = 0;
          $cv8 = 0;
          $cv9 = 0;
          $cv10 = 0;
          $mmr1 = 0;
          $mmr2 = 0;
          $mmr3 = 0;
          $mmr4 = 0;
          $cvv = $qcv->map(function ($q) use (&$cv1,&$cv2,&$cv3,&$cv4,&$cv5,&$cv6,&$cv7,&$cv8,&$cv9,&$cv10,&$mmr1,&$mmr2,&$mmr3,&$mmr4) {
			if ($q['stdev(con_price)/avg(con_price)*100']==0) {
			  	$cv1++;
			} else if ($q['stdev(con_price)/avg(con_price)*100']<=5 && $q['stdev(con_price)/avg(con_price)*100']>0) {
				$cv2++;
			} else if ($q['stdev(con_price)/avg(con_price)*100']<=10 && $q['stdev(con_price)/avg(con_price)*100']>5) {
				$cv3++;
			} else if ($q['stdev(con_price)/avg(con_price)*100']<=20 && $q['stdev(con_price)/avg(con_price)*100']>10) {
				$cv4++;
			} else if ($q['stdev(con_price)/avg(con_price)*100']<=30 && $q['stdev(con_price)/avg(con_price)*100']>20) {
				$cv5++;
			} else if ($q['stdev(con_price)/avg(con_price)*100']<=40 && $q['stdev(con_price)/avg(con_price)*100']>30) {
				$cv6++;
			} else if ($q['stdev(con_price)/avg(con_price)*100']<=50 && $q['stdev(con_price)/avg(con_price)*100']>40) {
				$cv7++;
			} else if ($q['stdev(con_price)/avg(con_price)*100']>50) {
				$cv8++;
			}
			if ($q['MIN(con_price)/MAX(con_price)']<0.1) {
			      $mmr1++;
			} elseif ($q['MIN(con_price)/MAX(con_price)']>=0.1 && $q['MIN(con_price)/MAX(con_price)']<0.2) {
				$mmr2++;
			} elseif ($q['MIN(con_price)/MAX(con_price)']>=0.2 && $q['MIN(con_price)/MAX(con_price)']<0.3) {
				$mmr3++;
			} elseif ($q['MIN(con_price)/MAX(con_price)']>=0.3) {
				$mmr4++;
			}
			return true;
		})->reject(function ($value) { return $value === false; });

    		return view('household.reports.six-sum', [
    			'country'         		=> $this->country,
    			'prod_avail'			=> $prod_avail,
    			'prod_priced'			=> $prod_priced,
    			'prod_quotations'		=> $quotations->count(),
    			'prod_q0'				=> ($prod_avail-$prod_priced),
    			'prod_q1'				=> Product::has('quotations', '=', '1')->count(),
    			'prod_q2'				=> Product::has('quotations', '=', '2')->count(),
    			'prod_q3'				=> $prodgreater3-$prodgreater5,
    			'prod_q4'				=> $prodgreater6-$prodgreater10,
    			'prod_q5'				=> $prodgreater11-$prodgreater14, //11 to 14
    			'prod_q6'				=> $prodgreater15-$prodgreater30, //15 to 30
    			'prod_q7'				=> $prodgreater30-$prodgreater90,
    			'prod_q8'				=> $prodgreater90,
    			'prod_q9'				=> $prodgreater15,
    			'prod_q10'			=> Product::has('quotations', '=', '1')->count()+Product::has('quotations', '=', '2')->count(),
    			'prod_q11'			=> $prodgreater3-$prodgreater15,

    			'prod_cv1'			=> ($cv1-Product::has('quotations', '=', '1')->count()),
    			'prod_cv2'			=> $cv2,
    			'prod_cv3'			=> $cv3,
    			'prod_cv4'			=> $cv4,
    			'prod_cv5'			=> $cv5,
    			'prod_cv6'			=> $cv6,
    			'prod_cv7'			=> $cv7,
    			'prod_cv8'			=> $cv8,

    			'prod_m1'				=> $mmr1,
    			'prod_m2'				=> $mmr2,
    			'prod_m3'				=> $mmr3,
    			'prod_m4'				=> $mmr4,

    			'start_date'	          => $this->start_date,
    			'end_date'    	          => $this->end_date,

    			'back'				=> ''
    		]);
    	}

	//formt columns
	public function columnFormats(): array
	{
		return [
		   // 'A' => NumberFormat::FORMAT_NUMBER,	
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
	          $sheet->getColumnDimension('A')->setWidth(28);
	          $sheet->getColumnDimension('B')->setWidth(20);
	          // $sheet->getColumnDimension('C')->setWidth(14);
	          // $sheet->getColumnDimension('H')->setWidth(14);
	          // $sheet->getPageSetup()->setFitToWidth(1);
	          // $sheet->freezePane('I8');
	          // $style = array(
	          //     'alignment' => array(
	          //         'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
	          //         'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
	          //     )
	          // );
	          // $sheet->getStyle("A6:H7")->applyFromArray($style);
	          $sheet->getStyle('A10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
	          $sheet->getStyle('A20')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
	          $sheet->getStyle('A24')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
	          $sheet->getStyle('A33')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
	          // $sheet->getStyle('C2:D4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fac3c8');
	      },
	  ];
	}

	/**
	* @return string
	*/
	public function title(): string
	{
		return 'Report 2';
	}
}
