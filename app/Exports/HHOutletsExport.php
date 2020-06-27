<?php

namespace App\Exports;

use App\Household\Outlet;
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

class HHOutletsExport implements FromView, withColumnFormatting, WithEvents, WithTitle
{
    	
        const FORMAT_NUMBER_CUSTOM_00  = '00';
    	const FORMAT_NUMBER_CUSTOM_000 = '000';
    	public function __construct($country)
    	{
    		$this->country = $country;
    	}

        //return location blade views
    	public function view(): View
    	{
    		$outlets = Outlet::where('ccode',$this->country->ccode)->get();
    		LOGG('SELECT','exported outlets data.');
    		return view('exports.outlets-hh', [
    			'outlets' => $outlets,
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
                'K' => self::FORMAT_NUMBER_CUSTOM_00
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

                $validation = $sheet->getCell('A11')->getDataValidation();
                $validation->setType(DataValidation::TYPE_WHOLE);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Only numbers between 01 and 99 are allowed.');
                $validation->setPromptTitle('Allowed input');
                $validation->setPrompt('Only numbers between 01 and 99 are allowed.');
                $validation->setFormula1(1);
                $validation->setFormula2(99);

                $validation2 = $sheet->getCell('B11')->getDataValidation();
                $validation2->setType(DataValidation::TYPE_WHOLE);
                $validation2->setErrorStyle(DataValidation::STYLE_STOP);
                $validation2->setAllowBlank(true);
                $validation2->setShowInputMessage(true);
                $validation2->setShowErrorMessage(true);
                $validation2->setErrorTitle('Input error');
                $validation2->setError('Only numbers between 00 and 99 are allowed.');
                $validation2->setPromptTitle('Allowed input');
                $validation2->setPrompt('Only numbers between 00 and 99 are allowed.');
                $validation2->setFormula1(0);
                $validation2->setFormula2(99);

                $validation3 = $sheet->getCell('D11')->getDataValidation();
                $validation3->setType(DataValidation::TYPE_WHOLE);
                $validation3->setErrorStyle(DataValidation::STYLE_STOP);
                $validation3->setAllowBlank(true);
                $validation3->setShowInputMessage(true);
                $validation3->setShowErrorMessage(true);
                $validation3->setErrorTitle('Input error');
                $validation3->setError('Only numbers between 01 and 99 are allowed.');
                $validation3->setPromptTitle('Allowed input');
                $validation3->setPrompt('Only numbers between 01 and 99 are allowed.');
                $validation3->setFormula1(0);
                $validation3->setFormula2(999);

                $validation4 = $sheet->getCell('H11')->getDataValidation();
                $validation4->setType(DataValidation::TYPE_WHOLE);
                $validation4->setErrorStyle(DataValidation::STYLE_STOP);
                $validation4->setAllowBlank(false);
                $validation4->setShowInputMessage(true);
                $validation4->setShowErrorMessage(true);
                $validation4->setShowDropDown(true);
                $validation4->setErrorTitle('Input error');
                $validation4->setError('Value is not in list.');
                $validation4->setPromptTitle('Pick from list');
                $validation4->setPrompt('Please pick a value from the drop-down list.');
                $validation4->setFormula1(1);
                $validation4->setFormula2(9);

                $validation5 = $sheet->getCell('I11')->getDataValidation();
                $validation5->setType(DataValidation::TYPE_WHOLE);
                $validation5->setErrorStyle(DataValidation::STYLE_STOP);
                $validation5->setAllowBlank(false);
                $validation5->setShowInputMessage(true);
                $validation5->setShowErrorMessage(true);
                $validation5->setShowDropDown(true);
                $validation5->setErrorTitle('Input error');
                $validation5->setError('Value is not in list.');
                $validation5->setPromptTitle('Pick from list');
                $validation5->setPrompt('Please pick a value from the drop-down list.');
                $validation5->setFormula1('loctype');
                $validation5->setFormula1(1);
                $validation5->setFormula2(2);

                if ($highestrow < 500) {
                    $highestrow = 500;
                }

                $sheet->setDataValidation("A11:A".$highestrow, $validation);
                $sheet->getStyle('A')->getNumberFormat()->setFormatCode('00');
                $sheet->getStyle('A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setDataValidation("B11:B".$highestrow, $validation2);
                $sheet->getStyle('B')->getNumberFormat()->setFormatCode('00');
                $sheet->getStyle('B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setDataValidation("C11:C".$highestrow, $validation2);
                $sheet->getStyle('C')->getNumberFormat()->setFormatCode('00');
                $sheet->getStyle('C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setDataValidation("D11:D".$highestrow, $validation3);
                $sheet->getStyle('D')->getNumberFormat()->setFormatCode('00');
                $sheet->getStyle('D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setDataValidation("E11:E".$highestrow, $validation3);
                $sheet->getStyle('E')->getNumberFormat()->setFormatCode('00');
                $sheet->getStyle('E')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setDataValidation("H11:H".$highestrow, $validation4);
                $sheet->getStyle('H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setDataValidation("I11:I".$highestrow, $validation4);
                $sheet->getStyle('I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


    			$sheet->getColumnDimension('K')->setVisible(false);
    			$sheet->setAutoFilter('A10:I10');
                $sheet->freezePane('J11');
               }
            ];
        }

    public function title(): string
    {
      return 'Outlets';
    }       
}
