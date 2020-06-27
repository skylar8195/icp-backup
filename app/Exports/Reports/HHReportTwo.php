<?php

namespace App\Exports\Reports;

use Auth;
use App\Country;
use App\Location;
use App\Household\Outlet;
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

class HHReportTwo implements FromView, withColumnFormatting, WithEvents, WithTitle
{
  	const FORMAT_ACCOUNTING = '_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)';
     
     //parameters
	public function __construct($country)
	{
		$this->country = $country;
	}

	//view
	public function view(): View
	{
		$country     = Country::where('ccode',Auth::User()->country)->first();
   		$locations   = Location::where('ccode',$country->ccode)->get();
    	$countrylevel = "";
        if ($country->loclvl3 == "") {
            $countrylevel = 2;
        } else if ($country->loclvl4 == "") {
            $countrylevel = 3;
        } else if ($country->loclvl5 == "") {
            $countrylevel = 4;
        } else {
            $countrylevel = 5;
        }

            $locations_outlets = $locations->map(function ($q) use ($countrylevel) {
            if ($q->loclvl < $countrylevel) {
                if ($q->loclvl == 2) {
                    $outlets = Outlet::where('lvl2',$q->loclvl2)->count();
                    $out_t1 = Outlet::where('lvl2',$q->loclvl2)->where('otype',1)->count();
                    $out_t2 = Outlet::where('lvl2',$q->loclvl2)->where('otype',2)->count();
                    $out_t3 = Outlet::where('lvl2',$q->loclvl2)->where('otype',3)->count();
                    $out_t4 = Outlet::where('lvl2',$q->loclvl2)->where('otype',4)->count();
                    $out_t5 = Outlet::where('lvl2',$q->loclvl2)->where('otype',5)->count();
                    $out_t6 = Outlet::where('lvl2',$q->loclvl2)->where('otype',6)->count();
                    $out_t7 = Outlet::where('lvl2',$q->loclvl2)->where('otype',7)->count();
                    $out_t8 = Outlet::where('lvl2',$q->loclvl2)->where('otype',8)->count();
                    $out_t9 = Outlet::where('lvl2',$q->loclvl2)->where('otype',9)->count();
                    $ol1  = Outlet::where('lvl2',$q->loclvl2)->where('loctype',1)->count();
                    $ol2  = Outlet::where('lvl2',$q->loclvl2)->where('loctype',2)->count();
                } else if ($q->loclvl == 3) {
                    $outlets = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->count();
                    $out_t1 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',1)->count();
                    $out_t2 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',2)->count();
                    $out_t3 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',3)->count();
                    $out_t4 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',4)->count();
                    $out_t5 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',5)->count();
                    $out_t6 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',6)->count();
                    $out_t7 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',7)->count();
                    $out_t8 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',8)->count();
                    $out_t9 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('otype',9)->count();
                    $ol1  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('loctype',1)->count();
                    $ol2  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('loctype',2)->count();
                } else if ($q->loclvl == 4) {
                    $outlets = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->count();
                    $out_t1 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',1)->count();
                    $out_t2 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',2)->count();
                    $out_t3 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',3)->count();
                    $out_t4 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',4)->count();
                    $out_t5 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',5)->count();
                    $out_t6 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',6)->count();
                    $out_t7 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',7)->count();
                    $out_t8 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',8)->count();
                    $out_t9 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('otype',9)->count();
                    $ol1  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('loctype',1)->count();
                    $ol2  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('loctype',2)->count();
                }
            } else {
$outlets = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->count();
$out_t1 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',1)->count();
$out_t2 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',2)->count();
$out_t3 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',3)->count();
$out_t4 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',4)->count();
$out_t5 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',5)->count();
$out_t6 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',6)->count();
$out_t7 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',7)->count();
$out_t8 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',8)->count();
$out_t9 = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('otype',9)->count();
$ol1  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('loctype',1)->count();
$ol2  = Outlet::where('lvl2',$q->loclvl2)->where('lvl3',$q->loclvl3)->where('lvl4',$q->loclvl4)->where('lvl5',$q->loclvl5)->where('loctype',2)->count();
            }
			return [
				 'loc_code'   		=> $q->loclvl2.$q->loclvl3.$q->loclvl4.$q->loclvl5,
				 'loc_name' 		=> $q->locname,
				 'total_outlets' 	=> $outlets,
				 'outlets_t1'	 	=> $out_t1,
				 'outlets_t2'	 	=> $out_t2,
				 'outlets_t3'	 	=> $out_t3,
				 'outlets_t4'	 	=> $out_t4,
				 'outlets_t5'	 	=> $out_t5,
				 'outlets_t6'	 	=> $out_t6,
				 'outlets_t7'	 	=> $out_t7,
				 'outlets_t8'	 	=> $out_t8,
				 'outlets_t9'	 	=> $out_t9,
				 'loctype1'		=> $ol1,
				 'loctype2'		=> $ol2
			];
		});
		LOGG('SELECT','generated report 2.');
		return view('household.reports.two', [
			'country'         	=> $this->country,
			'locations_outlets'	=> $locations_outlets,
			'back'			=> ''
		]);
	}

    //formt columns
    public function columnFormats(): array
    {
     return [
         'A' => NumberFormat::FORMAT_TEXT,
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
         'N' => self::FORMAT_ACCOUNTING
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
                $sheet->getColumnDimension('A')->setWidth(13);
                $sheet->getColumnDimension('B')->setWidth(40);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->freezePane('O9');
                $style = array(
                    'alignment' => array(
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    )
                );
                $sheet->getStyle("A6:N8")->applyFromArray($style);
                $sheet->getStyle('A6:N8')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
            },
        ];
    }
    /**
    * @return string
    */
    public function title(): string
    {
        return 'Report 4';
    }    
}
