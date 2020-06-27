<?php

namespace App\Exports;

use DB;
use Log;
use App\Household\Product;
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

class HHProductsExport implements FromView, withColumnFormatting, WithEvents, WithTitle
{
	const FORMAT_ACCOUNTING = '_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)';
	const FORMAT_PCODE = '00"."00"."00"."0"."00"."0';

	public function __construct($country)
	{
		$this->country = $country;
	}

    //return location blade view
	public function view(): View
	{
		$products = Product::orderBy('pcode','asc')->get();
		LOGG('SELECT','exported products data.');
		return view('exports.products-hh', [
			'products' => $products,
			'country' => $this->country
		]);
	}

	public function columnFormats(): array
     {
        return [
            'A' => self::FORMAT_PCODE,
            'F' => self::FORMAT_ACCOUNTING,
            'G' => self::FORMAT_ACCOUNTING,
            'H' => self::FORMAT_ACCOUNTING,
        ];
     }

    //format excel sheet
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
            	 $highestrow = $event->getSheet()->getHighestRow();
            	 $highestcol = $event->getSheet()->getHighestColumn();
                $protection = $event->getSheet()->getDelegate()->getProtection();
                              $protection->setPassword('ADBICPTeam');
                              $protection->setSheet(true);
                              $protection->setSort(true);
                              $protection->setInsertRows(true);
                              $protection->setFormatCells(true);
          	 $event->getSheet()
                	  ->getStyle('C11:'.'D'.$highestrow)->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
			$sheet = $event->sheet;
			$sheet->SetCellValue( "E5", "M" );
			$sheet->SetCellValue( "E6", "Q" );
			$sheet->SetCellValue( "E7", "S" );
			$sheet->SetCellValue( "E8", "A" );
			$sheet->getParent()->addNamedRange(new NamedRange('frequency', $sheet->getDelegate(), 'E5:E8'));


			$validation = $sheet->getCell('C11')->getDataValidation();
			$validation->setType(DataValidation::TYPE_WHOLE);
			$validation->setErrorStyle(DataValidation::STYLE_STOP);
			$validation->setAllowBlank(false);
			$validation->setShowInputMessage(true);
			$validation->setShowErrorMessage(true);
			$validation->setShowDropDown(true);
			$validation->setErrorTitle('Input error');
			$validation->setError('Value is not in list.');
			$validation->setPromptTitle('Pick from list');
			$validation->setPrompt('Please pick a value from the drop-down list.');
			$validation->setFormula1(0);
			$validation->setFormula2(2);
			$sheet->setDataValidation("C11:C".$highestrow, $validation);

			$validation2 = $sheet->getCell('D11')->getDataValidation();
			$validation2->setType(DataValidation::TYPE_LIST);
			$validation2->setErrorStyle(DataValidation::STYLE_STOP);
			$validation2->setAllowBlank(false);
			$validation2->setShowInputMessage(true);
			$validation2->setShowErrorMessage(true);
			$validation2->setShowDropDown(true);
			$validation2->setErrorTitle('Input error');
			$validation2->setError('Value is not in list.');
			$validation2->setPromptTitle('Pick from list');
			$validation2->setPrompt('Please pick a value from the drop-down list.');
			$validation2->setFormula1('frequency');
			$sheet->setDataValidation("D11:D".$highestrow, $validation2);
			
			$sheet->getColumnDimension('E')->setVisible(false);
			$sheet->setAutoFilter('A10:K10');
			$sheet->freezePane('L11');
            },
        ];
    }

    public function title(): string
    {
      return 'Products';
    }   	
}
