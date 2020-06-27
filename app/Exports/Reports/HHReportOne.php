<?php

namespace App\Exports\Reports;

use Auth;
use App\Country;
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

class HHReportOne implements FromView, withColumnFormatting, WithEvents, WithTitle
{
    const FORMAT_ACCOUNTING = '_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)';
    // const FORMAT_PCODE = '00"."00"."00"."0"."00"."0';
    
    //parameters
	public function __construct($country)
	{
		$this->country = $country;
	}

	//view
	public function view(): View
	{
		$country     = Country::where('ccode',Auth::User()->country)->first();
        $prod_count        = Product::count();
        $prod_count_na     = Product::where('avail',0)->count();
        $prod_count_av     = Product::where('avail',1)->count();
        $prod_count_avl    = Product::where('avail',2)->count();
        $prodclasses = ProductClassification::all();
        $prodclasses = $prodclasses->map(function ($q) {
              if ($q->classid != "1110000") {
                $classid_trim = rtrim($q->classid, "0");
              } else {
                $classid_trim = "1110";
              }
              $prod = Product::where('pcode','like', $classid_trim.'%')->count();
              $prod_na = Product::where('avail',0)->where('pcode','like', $classid_trim.'%')->count();
              $prod_av = Product::where('avail',1)->where('pcode','like', $classid_trim.'%')->count();
              $prod_avl = Product::where('avail',2)->where('pcode','like', $classid_trim.'%')->count();
              return [
                  'classid'         => $classid_trim,
                  'classdesc'       => $q->classdesc,
                  'total_count'     => $prod,
                  'total_count_na'  => $prod_na,
                  'total_count_av'  => $prod_av,
                  'total_count_avl'     => $prod_avl
              ];
        });

		LOGG('SELECT','generated report 1.');
		return view('household.reports.one', [
			'prodclasses'     => $prodclasses,
			'country'         => $this->country,
            'prod_count'      => Product::all()->count(),
            'prod_count_na'   => Product::where('avail',0)->count(),
            'prod_count_av'   => Product::where('avail',1)->count(),
            'prod_count_avl'  => Product::where('avail',2)->count(),
            'back'               => ''
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
         'F' => self::FORMAT_ACCOUNTING
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
                $sheet->getColumnDimension('B')->setWidth(60);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->freezePane('G10');
                $style = array(
                    'alignment' => array(
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    )
                );
                $sheet->getStyle("A7:F9")->applyFromArray($style);
                $sheet->getStyle('A7:F9')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
            },
        ];
    }

    /**
    * @return string
    */
    public function title(): string
    {
      return 'Report 3';
    }
}
