<?php

namespace App\Household;

use Illuminate\Database\Eloquent\Model;

class Mapping extends Model
{
    protected $table = "household_mappings";
    protected $fillable = [
        'id','ocode','pcode','oid'
    ];
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function outlet()
    {
       return $this->hasMany(Outlet::class,'id','oid');
    }

    public function product()
    {
       return $this->hasMany(Product::class,'pcode','pcode');
    }
}
