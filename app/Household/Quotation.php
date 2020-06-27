<?php

namespace App\Household;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Quotation extends Model
{
    protected $table = "household_quotations";
    protected $fillable = [
        'pcode','ocode','obv_qty','obv_date','price','con_price','price_type','remarks','brand','ref_period','ref_year','ref_months','encoder','oid'
    ];

    public function product()
    {
       return $this->belongsTo(Product::class,'pcode','pcode');
    }

    public function outlet()
    {
       return $this->hasOne(Outlet::class,'id','oid');
    }

    public function encoder()
    {
       return $this->hasOne(User::class,'id','encoder');
    }
}
