<?php
namespace App\Exports;

use Log;
use App\Location;
use App\Country;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\NamedRange;

class LocationsExport implements FromView, WithColumnFormatting, WithEvents, WithTitle
{
  const FORMAT_NUMBER_CUSTOM_00  = '00';
  const FORMAT_NUMBER_CUSTOM_000 = '000';
	//accept ccode, isdata from function call
	public function __construct($ccode,$isData)
	{
		$this->ccode = $ccode;
		$this->isData = $isData;
	}

	//return location blade view
	public function view(): View
	{
		$country = Country::where('ccode',$this->ccode)->first();
		$locations = $country->locations()->get();
		Log::info('User downloaded location template');
		return view('tables.location', [
			'country' => $country,
			'locations' => $locations,
			'isData' => $this->isData
		]);
	}

  public function columnFormats(): array
   {
     return [
         'A' => self::FORMAT_NUMBER_CUSTOM_00,
         'B' => self::FORMAT_NUMBER_CUSTOM_00,
         'C' => self::FORMAT_NUMBER_CUSTOM_00,
         'D' => self::FORMAT_NUMBER_CUSTOM_000
     ];
   }

    //format excel sheet
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
            	 $highestrow = $event->getSheet()->getHighestRow();
            	 $highestcol = $event->getSheet()->getHighestColumn();
               $protection = $event->getSheet()->getDelegate()->getProtection();
                              $protection->setPassword('ADBICPTeam');
                              $protection->setSheet(true);
                              $protection->setSort(true);
                              $protection->setDeleteRows(false);
                              $protection->setInsertRows(true);
                              $protection->setFormatCells(true);
              $event->sheet->getStyle('A:F')->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
              $event->sheet->getStyle('A9:F'.$highestrow)->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);

              $sheet = $event->sheet;

              $sheet->SetCellValue( "G1", "Yes" );
              $sheet->SetCellValue( "G2", "No" );
              $sheet->getParent()->addNamedRange(new NamedRange('capcity', $sheet->getDelegate(), 'G1:G2'));

              if ($highestrow < 500) {
                  $highestrow = 500;
              }

              $validation = $sheet->getCell('A9')->getDataValidation();
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
              $sheet->setDataValidation("A9:A".$highestrow, $validation);
              $sheet->getStyle('A')->getNumberFormat()->setFormatCode('00');
              $sheet->getStyle('A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

              $validation2 = $sheet->getCell('B9')->getDataValidation();
              $validation2->setType(DataValidation::TYPE_WHOLE);
              $validation2->setErrorStyle(DataValidation::STYLE_STOP);
              $validation2->setAllowBlank(true);
              $validation2->setShowInputMessage(true);
              $validation2->setShowErrorMessage(true);
              $validation2->setErrorTitle('Input error');
              $validation2->setError('Only numbers between 0 and 99 are allowed.');
              $validation2->setPromptTitle('Allowed input');
              $validation2->setPrompt('Only numbers between 0 and 99 are allowed.');
              $validation2->setFormula1(0);
              $validation2->setFormula2(99);
              $sheet->setDataValidation("B9:B".$highestrow, $validation);
              $sheet->getStyle('B')->getNumberFormat()->setFormatCode('00');
              $sheet->getStyle('B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
              $sheet->setDataValidation("C9:C".$highestrow, $validation);
              $sheet->getStyle('C')->getNumberFormat()->setFormatCode('00');
              $sheet->getStyle('C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

              $validation2 = $sheet->getCell('D9')->getDataValidation();
              $validation2->setType(DataValidation::TYPE_WHOLE);
              $validation2->setErrorStyle(DataValidation::STYLE_STOP);
              $validation2->setAllowBlank(true);
              $validation2->setShowInputMessage(true);
              $validation2->setShowErrorMessage(true);
              $validation2->setErrorTitle('Input error');
              $validation2->setError('Only numbers between 01 and 999 are allowed.');
              $validation2->setPromptTitle('Allowed input');
              $validation2->setPrompt('Only numbers between 01 and 999 are allowed.');
              $validation2->setFormula1(0);
              $validation2->setFormula2(999);
              $sheet->setDataValidation("D9:D".$highestrow, $validation);
              $sheet->getStyle('D')->getNumberFormat()->setFormatCode('00');
              $sheet->getStyle('D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

              $validation3 = $sheet->getCell('F9')->getDataValidation();
              $validation3->setType(DataValidation::TYPE_LIST);
              $validation3->setErrorStyle(DataValidation::STYLE_STOP);
              $validation3->setAllowBlank(false);
              $validation3->setShowInputMessage(true);
              $validation3->setShowErrorMessage(true);
              $validation3->setShowDropDown(true);
              $validation3->setErrorTitle('Input error');
              $validation3->setError('Value is not in list.');
              $validation3->setPromptTitle('Pick from list');
              $validation3->setPrompt('Please pick a value from the drop-down list.');
              $validation3->setFormula1('capcity');
              $sheet->setDataValidation("F9:F".$highestrow, $validation3);
              $sheet->getStyle('F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


              // $style = array(
              //     'alignment' => array(
              //         'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
              //         'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
              //     )
              // );
              // $event->sheet->getStyle("E1:E".$highestrow)->applyFromArray($style);
              $event->sheet->getStyle("A")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
              $event->sheet->getStyle("B")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
              $event->sheet->getStyle("C")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
              $event->sheet->getStyle("D")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
              $event->sheet->getStyle("E")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
              $sheet->getColumnDimension('G')->setVisible(false);
              $event->sheet->freezePane('G9');
            },
        ];
    }
    public function title(): string
    {
      return 'Locations';
    }     
}
