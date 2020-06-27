<?php

namespace App\Exports;
use DB;
use Auth;
use App\Household\Mapping;
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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithTitle;

class HHMappingsExport implements FromView, withColumnFormatting, WithEvents, WithTitle
{
	const FORMAT_NUMBER_CUSTOM_00  = '00';
	const FORMAT_NUMBER_CUSTOM_000 = '000';
    const FORMAT_PCODE = '00"."00"."00"."0"."00"."0';

    //construct variable
    public function __construct($country)
    {
    	$this->country = $country;
    }

     //return location blade views
    	public function view(): View
    	{
    		$mappings = DB::table('household_mappings')
                        ->leftJoin('household_outlets','household_outlets.id','=','household_mappings.oid')
                        ->leftJoin('household_products','household_products.pcode','=','household_mappings.pcode')
                        ->where('household_outlets.ccode',Auth::User()->country)
                        ->get();

    		LOGG('SELECT','exported mappings data.');
    		return view('exports.mappings-hh', [
    			'mappings' => $mappings,
    			'country' => $this->country
    		]);
    	}


    	public function columnFormats(): array
     {
       return [
           'A' => self::FORMAT_NUMBER_CUSTOM_00,
           'B' => self::FORMAT_NUMBER_CUSTOM_00,
           'C' => self::FORMAT_NUMBER_CUSTOM_00,
           'D' => self::FORMAT_NUMBER_CUSTOM_000,
           'E' => self::FORMAT_NUMBER_CUSTOM_000,
           'F' => self::FORMAT_PCODE,
           'G' => NumberFormat::FORMAT_TEXT,
           'H' => NumberFormat::FORMAT_TEXT
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

               // $cellRange = 'A1:W1'; // All headers
               // $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
              	$event->sheet->getStyle('A11:I'.$highestrow)->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
              	$event->sheet->getStyle('A:I')->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
    			$sheet = $event->sheet;
    			// for ($x = 11; $x <= 500; $x++) {

    			//     $sheet->getCell('A'.$x)->getDataValidation()->setType(DataValidation::TYPE_WHOLE);
    			//     $sheet->getCell('A'.$x)->getDataValidation()->setErrorStyle(DataValidation::STYLE_STOP);
    			//     $sheet->getCell('A'.$x)->getDataValidation()->setAllowBlank(true);
    			//     $sheet->getCell('A'.$x)->getDataValidation()->setShowInputMessage(true);
    			//     $sheet->getCell('A'.$x)->getDataValidation()->setShowErrorMessage(true);
    			//     $sheet->getCell('A'.$x)->getDataValidation()->setErrorTitle('Input error');
    			//     $sheet->getCell('A'.$x)->getDataValidation()->setError('Only numbers between 01 and 99 are allowed.');
    			//     $sheet->getCell('A'.$x)->getDataValidation()->setPromptTitle('Allowed input');
    			//     $sheet->getCell('A'.$x)->getDataValidation()->setPrompt('Only numbers between 01 and 99 are allowed.');
    			//     $sheet->getCell('A'.$x)->getDataValidation()->setFormula1(1);
    			//     $sheet->getCell('A'.$x)->getDataValidation()->setFormula2(99);

    			//     $sheet->getCell('B'.$x)->getDataValidation()->setType(DataValidation::TYPE_WHOLE);
    			//     $sheet->getCell('B'.$x)->getDataValidation()->setErrorStyle(DataValidation::STYLE_STOP);
    			//     $sheet->getCell('B'.$x)->getDataValidation()->setAllowBlank(true);
    			//     $sheet->getCell('B'.$x)->getDataValidation()->setShowInputMessage(true);
    			//     $sheet->getCell('B'.$x)->getDataValidation()->setShowErrorMessage(true);
    			//     $sheet->getCell('B'.$x)->getDataValidation()->setErrorTitle('Input error');
    			//     $sheet->getCell('B'.$x)->getDataValidation()->setError('Only numbers between 00 and 99 are allowed.');
    			//     $sheet->getCell('B'.$x)->getDataValidation()->setPromptTitle('Allowed input');
    			//     $sheet->getCell('B'.$x)->getDataValidation()->setPrompt('Only numbers between 00 and 99 are allowed.');
    			//     $sheet->getCell('B'.$x)->getDataValidation()->setFormula1(0);
    			//     $sheet->getCell('B'.$x)->getDataValidation()->setFormula2(99);

    			//     $sheet->getCell('C'.$x)->getDataValidation()->setType(DataValidation::TYPE_WHOLE);
    			//     $sheet->getCell('C'.$x)->getDataValidation()->setErrorStyle(DataValidation::STYLE_STOP);
    			//     $sheet->getCell('C'.$x)->getDataValidation()->setAllowBlank(true);
    			//     $sheet->getCell('C'.$x)->getDataValidation()->setShowInputMessage(true);
    			//     $sheet->getCell('C'.$x)->getDataValidation()->setShowErrorMessage(true);
    			//     $sheet->getCell('C'.$x)->getDataValidation()->setErrorTitle('Input error');
    			//     $sheet->getCell('C'.$x)->getDataValidation()->setError('Only numbers between 00 and 99 are allowed.');
    			//     $sheet->getCell('C'.$x)->getDataValidation()->setPromptTitle('Allowed input');
    			//     $sheet->getCell('C'.$x)->getDataValidation()->setPrompt('Only numbers between 00 and 99 are allowed.');
    			//     $sheet->getCell('C'.$x)->getDataValidation()->setFormula1(0);
    			//     $sheet->getCell('C'.$x)->getDataValidation()->setFormula2(99);

    			//     $sheet->getCell('D'.$x)->getDataValidation()->setType(DataValidation::TYPE_WHOLE);
    			//     $sheet->getCell('D'.$x)->getDataValidation()->setErrorStyle(DataValidation::STYLE_STOP);
    			//     $sheet->getCell('D'.$x)->getDataValidation()->setAllowBlank(true);
    			//     $sheet->getCell('D'.$x)->getDataValidation()->setShowInputMessage(true);
    			//     $sheet->getCell('D'.$x)->getDataValidation()->setShowErrorMessage(true);
    			//     $sheet->getCell('D'.$x)->getDataValidation()->setErrorTitle('Input error');
    			//     $sheet->getCell('D'.$x)->getDataValidation()->setError('Only numbers between 00 and 999 are allowed.');
    			//     $sheet->getCell('D'.$x)->getDataValidation()->setPromptTitle('Allowed input');
    			//     $sheet->getCell('D'.$x)->getDataValidation()->setPrompt('Only numbers between 00 and 999 are allowed.');
    			//     $sheet->getCell('D'.$x)->getDataValidation()->setFormula1(0);
    			//     $sheet->getCell('D'.$x)->getDataValidation()->setFormula2(999);

    			//     $sheet->getCell('E'.$x)->getDataValidation()->setType(DataValidation::TYPE_WHOLE);
    			//     $sheet->getCell('E'.$x)->getDataValidation()->setErrorStyle(DataValidation::STYLE_STOP);
    			//     $sheet->getCell('E'.$x)->getDataValidation()->setAllowBlank(true);
    			//     $sheet->getCell('E'.$x)->getDataValidation()->setShowInputMessage(true);
    			//     $sheet->getCell('E'.$x)->getDataValidation()->setShowErrorMessage(true);
    			//     $sheet->getCell('E'.$x)->getDataValidation()->setErrorTitle('Input error');
    			//     $sheet->getCell('E'.$x)->getDataValidation()->setError('Only numbers between 01 and 999 are allowed.');
    			//     $sheet->getCell('E'.$x)->getDataValidation()->setPromptTitle('Allowed input');
    			//     $sheet->getCell('E'.$x)->getDataValidation()->setPrompt('Only numbers between 01 and 999 are allowed.');
    			//     $sheet->getCell('E'.$x)->getDataValidation()->setFormula1(1);
    			//     $sheet->getCell('E'.$x)->getDataValidation()->setFormula2(999);
    			// }
    			$event->sheet->getStyle('A:C')->getNumberFormat()->setFormatCode('00');
    			// $event->sheet->getStyle('F9:H'.$highestrow)->getNumberFormat()->setFormatCode('0');
    			// $event->sheet->getStyle("F")->getNumberFormat()->setFormatCode('0');	

    			// $spreadsheet->getActiveSheet()->getStyle('A2:A4,K12:G20')->applyFromArray($styleArray);

    			// 'alignment' => [
    			    // 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    			    // 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    			// ],

    			$style = array(
		        'alignment' => array(
    		            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    		            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    		        )
    		    );
    			$event->sheet->getStyle("A9:H".$highestrow)->applyFromArray($style);
                $style = array(
                'alignment' => array(
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    )
                );
                $event->sheet->getStyle("G11:H".$highestrow)->applyFromArray($style);

                $event->sheet->getStyle("A")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle("B")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle("C")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle("D")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle("E")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle("F")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    			#Using H
    			// $sheet->getColumnDimension('K')->setVisible(false);
    			$sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(42);
                $sheet->setAutoFilter('A10:F10');
                $sheet->freezePane('G11');

               }
            ];
        }

    public function title(): string
    {
      return 'Mappings';
    }       
}
