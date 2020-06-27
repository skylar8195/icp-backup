<?php

namespace App\Household;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = "household_products";
    protected $primaryKey = 'pcode'; 
    protected $fillable = [
        'avail', 'impt'
    ];
    public $timestamps = false;
    
    public function uom()
    {
       return $this->hasOne(UOM::class,'uomid','uomid');
    }

    public function quotations()
    {
       return $this->hasMany(Quotation::class,'pcode','pcode');
    }

    public function scopeNotAvailable($query)
    {
      return $query->where('avail', 0);
    }

    public function scopeAvailable($query)
    {
      return $query->where('avail', 1);
    }

    public function scopeAvailableLess($query)
    {
      return $query->where('avail', 2);
    }

    public function scopeWithQuotationsGreaterThan($query, $number)
    {
      return $query->has('quotations', '>', $number);
    }

    public function getTotalQuotesAttribute()
     {
        return $this->hasMany(Quotation::class,'pcode','pcode')->wherepcode($this->pcode)->count();

     }
}
