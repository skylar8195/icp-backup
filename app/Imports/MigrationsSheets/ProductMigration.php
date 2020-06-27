<?php

namespace App\Imports\MigrationsSheets;

use App\Household\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ProductMigration implements ToCollection, WithStartRow
{
    public $count = 10;
    public function collection(Collection $rows)
    {
		foreach ($rows as $row) {
			Product::where('concordance2017',$row[0])->update(['avail'=>$row[1]]);
			++$this->count;
		}
    }

    public function startRow(): int
    {
        return 10;
    }
}
