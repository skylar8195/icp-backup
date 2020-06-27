<?php

namespace App\Exports;

use App\Household\Mapping;
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
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\PageMargins;
use Maatwebsite\Excel\Concerns\WithTitle;

class HHSurveyExport implements FromView, withColumnFormatting, WithEvents, WithTitle
{
  const FORMAT_NUMBER_CUSTOM_00  = '00';
	const FORMAT_NUMBER_CUSTOM_000 = '000';
  const FORMAT_PCODE = '00"."00"."00"."0"."00"."0';

   //construct variable
   public function __construct($country,$collector,$freq,$year,$month,$selected_array)
   {
   	$this->country = $country;
    $this->collector = $collector;
    $this->freq = $freq;
    $this->year = $year;
    $this->month = $month;
    $this->selected_array = $selected_array;
   }

    //return location blade views
   	public function view(): View
   	{
      if (count($this->selected_array) > 0 && $this->selected_array[0] != "") {
        $outlets = collect();
        foreach ($this->selected_array as $outletid) {
            $outlets->add(Outlet::with('mappings')->where('id',$outletid)->first());
        }
      } else {
        $outlets = Outlet::has('mappings')->where('ccode',$this->country->ccode)->get();
      }

   		LOGG('SELECT','exported survey generation.');
   		return view('exports.surveys-hh', [
   			'country' => $this->country,
   			'outlets' => $outlets,
        'collector' => $this->collector,
        'freq' => $this->freq,
        'year' => $this->year,
        'month' => $this->month
   		]);
   	}


   	public function columnFormats(): array
  	{
  	 return [
  	     'A' => self::FORMAT_NUMBER_CUSTOM_00,
  	     'B' => self::FORMAT_NUMBER_CUSTOM_00,
  	     'C' => self::FORMAT_NUMBER_CUSTOM_00,
  	     'D' => self::FORMAT_PCODE,
  	     'E' => self::FORMAT_NUMBER_CUSTOM_000,
  	     'F' => NumberFormat::FORMAT_TEXT,
         'M' => self::FORMAT_NUMBER_CUSTOM_00,
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
             	$event->sheet->getStyle('L8:S'.$highestrow)->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
             	$event->sheet->getStyle('L:S')->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
   			      $sheet = $event->sheet;



         			$sheet->SetCellValue( "T1", "R" );
         			$sheet->SetCellValue( "T2", "B" );
              $sheet->SetCellValue( "T3", "D" );
         			$sheet->getParent()->addNamedRange(new NamedRange('pricetype', $sheet->getDelegate(), 'T1:T3'));
         			
         			for ($x = 9; $x <= $highestrow; $x++) {

                  $sheet->getRowDimension($x)->setRowHeight(-1);
         			    $sheet->getCell('L'.$x)->getDataValidation()->setType(DataValidation::TYPE_DECIMAL);
         			    $sheet->getCell('L'.$x)->getDataValidation()->setErrorStyle(DataValidation::STYLE_STOP);
         			    $sheet->getCell('L'.$x)->getDataValidation()->setAllowBlank(true);
         			    $sheet->getCell('L'.$x)->getDataValidation()->setShowInputMessage(true);
         			    $sheet->getCell('L'.$x)->getDataValidation()->setShowErrorMessage(true);
         			    $sheet->getCell('L'.$x)->getDataValidation()->setErrorTitle('Input error');
         			    $sheet->getCell('L'.$x)->getDataValidation()->setError('Quantity must be within MIN and MAX range.');
         			    $sheet->getCell('L'.$x)->getDataValidation()->setPromptTitle('Allowed input');
         			    $sheet->getCell('L'.$x)->getDataValidation()->setPrompt('Quantity must be within MIN and MAX range.');
                  $min = $sheet->getCell('J'.$x)->getValue();
                  $max = $sheet->getCell('K'.$x)->getValue();
         			    $sheet->getCell('L'.$x)->getDataValidation()->setFormula1($min);
         			    $sheet->getCell('L'.$x)->getDataValidation()->setFormula2($max);

                  $sheet->getCell('M'.$x)->getDataValidation()->setType(DataValidation::TYPE_DECIMAL);
                  $sheet->getCell('M'.$x)->getDataValidation()->setErrorStyle(DataValidation::STYLE_STOP);
                  $sheet->getCell('M'.$x)->getDataValidation()->setAllowBlank(true);
                  $sheet->getCell('M'.$x)->getDataValidation()->setShowInputMessage(true);
                  $sheet->getCell('M'.$x)->getDataValidation()->setShowErrorMessage(true);
                  $sheet->getCell('M'.$x)->getDataValidation()->setErrorTitle('Input error');
                  $sheet->getCell('M'.$x)->getDataValidation()->setError('Only numbers are allowed.');
                  $sheet->getCell('M'.$x)->getDataValidation()->setPromptTitle('Allowed input');
                  $sheet->getCell('M'.$x)->getDataValidation()->setPrompt('Only numbers are allowed.');
                  $sheet->getCell('M'.$x)->getDataValidation()->setFormula1(1);
                  $sheet->getCell('M'.$x)->getDataValidation()->setFormula2(9999999);

                  $sheet->getCell('N'.$x)->getDataValidation()->setType(DataValidation::TYPE_LIST);
                  $sheet->getCell('N'.$x)->getDataValidation()->setErrorStyle(DataValidation::STYLE_STOP);
                  $sheet->getCell('N'.$x)->getDataValidation()->setAllowBlank(false);
                  $sheet->getCell('N'.$x)->getDataValidation()->setShowInputMessage(true);
                  $sheet->getCell('N'.$x)->getDataValidation()->setShowErrorMessage(true);
                  $sheet->getCell('N'.$x)->getDataValidation()->setShowDropDown(true);
                  $sheet->getCell('N'.$x)->getDataValidation()->setErrorTitle('Input error');
                  $sheet->getCell('N'.$x)->getDataValidation()->setError('Value is not in list.');
                  $sheet->getCell('N'.$x)->getDataValidation()->setPromptTitle('Pick from list');
                  $sheet->getCell('N'.$x)->getDataValidation()->setPrompt('Please pick a value from the drop-down list.');
                  $sheet->getCell('N'.$x)->getDataValidation()->setFormula1('pricetype');

                  $sheet->getCell('O'.$x)->getDataValidation()->setType(DataValidation::TYPE_WHOLE);
                  $sheet->getCell('O'.$x)->getDataValidation()->setErrorStyle(DataValidation::STYLE_STOP);
                  $sheet->getCell('O'.$x)->getDataValidation()->setAllowBlank(true);
                  $sheet->getCell('O'.$x)->getDataValidation()->setShowInputMessage(true);
                  $sheet->getCell('O'.$x)->getDataValidation()->setShowErrorMessage(true);
                  $sheet->getCell('O'.$x)->getDataValidation()->setErrorTitle('Input error');
                  $sheet->getCell('O'.$x)->getDataValidation()->setError('Only numbers are allowed.');
                  $sheet->getCell('O'.$x)->getDataValidation()->setPromptTitle('Allowed input');
                  $sheet->getCell('O'.$x)->getDataValidation()->setPrompt('Only numbers are allowed.');
                  $sheet->getCell('O'.$x)->getDataValidation()->setFormula1(1);
                  $sheet->getCell('O'.$x)->getDataValidation()->setFormula2(31);

                  $sheet->getCell('P'.$x)->getDataValidation()->setType(DataValidation::TYPE_WHOLE);
                  $sheet->getCell('P'.$x)->getDataValidation()->setErrorStyle(DataValidation::STYLE_STOP);
                  $sheet->getCell('P'.$x)->getDataValidation()->setAllowBlank(true);
                  $sheet->getCell('P'.$x)->getDataValidation()->setShowInputMessage(true);
                  $sheet->getCell('P'.$x)->getDataValidation()->setShowErrorMessage(true);
                  $sheet->getCell('P'.$x)->getDataValidation()->setErrorTitle('Input error');
                  $sheet->getCell('P'.$x)->getDataValidation()->setPromptTitle('Allowed input');

                  if ($this->freq == "M") {
                    $sheet->getCell('P'.$x)->getDataValidation()->setFormula1($this->month);
                    $sheet->getCell('P'.$x)->getDataValidation()->setFormula2($this->month);
                    $sheet->getCell('P'.$x)->getDataValidation()->setError('Only specified coverage period is allowed. Specified month: '.$this->month);
                    $sheet->getCell('P'.$x)->getDataValidation()->setPrompt('Only specified coverage period is allowed. Specified month: '.$this->month);
                  } else {
                    $sheet->getCell('P'.$x)->getDataValidation()->setError('Only specified coverage period is allowed. Specified months: '.substr($this->month, 0,2).'-'.substr($this->month, 2,4));
                    $sheet->getCell('P'.$x)->getDataValidation()->setPrompt('Only specified coverage period is allowed. Specified months: '.substr($this->month, 0,2).'-'.substr($this->month, 2,4));
                    $sheet->getCell('P'.$x)->getDataValidation()->setFormula1(substr($this->month, 0,2));
                    $sheet->getCell('P'.$x)->getDataValidation()->setFormula2(substr($this->month, 2,4));
                  }

                  $sheet->getCell('Q'.$x)->getDataValidation()->setType(DataValidation::TYPE_WHOLE);
                  $sheet->getCell('Q'.$x)->getDataValidation()->setErrorStyle(DataValidation::STYLE_STOP);
                  $sheet->getCell('Q'.$x)->getDataValidation()->setAllowBlank(true);
                  $sheet->getCell('Q'.$x)->getDataValidation()->setShowInputMessage(true);
                  $sheet->getCell('Q'.$x)->getDataValidation()->setShowErrorMessage(true);
                  $sheet->getCell('Q'.$x)->getDataValidation()->setErrorTitle('Input error');
                  $sheet->getCell('Q'.$x)->getDataValidation()->setError('Only numbers are allowed.');
                  $sheet->getCell('Q'.$x)->getDataValidation()->setPromptTitle('Allowed input');
                  $sheet->getCell('Q'.$x)->getDataValidation()->setPrompt('Only numbers are allowed.');
                  $sheet->getCell('Q'.$x)->getDataValidation()->setFormula1(2020);
                  $sheet->getCell('Q'.$x)->getDataValidation()->setFormula2(2021);
         			}
         			$event->sheet->getStyle('A:D')->getNumberFormat()->setFormatCode('00');

         			// $sheet->getDefaultStyle()
         			//         ->getAlignment()
         			//         ->applyFromArray(array(
         			//           'vertical'     	=> \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
         			//           'wrap'		 	=> TRUE
         			// ));
         			$event->sheet->getDelegate()->getStyle('G9:G'.$highestrow)->getAlignment()->setWrapText(true);
              $event->sheet->getDelegate()->getStyle('C9:D'.$highestrow)->getAlignment()->setWrapText(true);
         			// $event->sheet->getStyle('G10')->getAlignment()->setIndent(1);

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
         			$event->sheet->getStyle("A9:R".$highestrow)->applyFromArray($style);

         			$style2 = array(
        	        'alignment' => array(
        	            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
        	            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
        	        )
      	    	);
         			$event->sheet->getStyle("G9:G".$highestrow)->applyFromArray($style2);

              $style3 = array(
                  'alignment' => array(
                      'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                      'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                  )
              );
              $event->sheet->getStyle("C9:C".$highestrow)->applyFromArray($style3);
         			
              $sheet->getPageMargins()->setTop(0.1)->setBottom(0.1)->setLeft(0.1)->setRight(0.1);
              $sheet->getColumnDimension('I')->setVisible(false);
         			$sheet->getColumnDimension('A')->setVisible(false);
         			$sheet->getColumnDimension('B')->setVisible(false);
              $sheet->getColumnDimension('T')->setVisible(false);
              $sheet->getColumnDimension('J')->setVisible(false);
              $sheet->getColumnDimension('K')->setVisible(false);
              $sheet->getColumnDimension('C')->setWidth(19);
              $sheet->getColumnDimension('F')->setWidth(12);
              $sheet->getColumnDimension('J')->setWidth(8);
              $sheet->getColumnDimension('K')->setWidth(8);
              $sheet->getColumnDimension('L')->setWidth(11);
              $sheet->getColumnDimension('M')->setWidth(13);
         			$sheet->getColumnDimension('N')->setWidth(13);
              $sheet->getColumnDimension('O')->setWidth(12);
              $sheet->getColumnDimension('P')->setWidth(15);
              $sheet->getColumnDimension('Q')->setWidth(15);

              $sheet->setPrintGridlines(TRUE);
              $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(8,8);
              $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
              $sheet->getPageSetup()->setFitToWidth(1);
              $sheet->getPageSetup()->setFitToHeight(0);
              $sheet->setAutoFilter('A8:S8');
              $sheet->freezePane('L9');
            }
          ];
       }
      public function title(): string
      {
        return 'Questionnaire';
      }     
}
