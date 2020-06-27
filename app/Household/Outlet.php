<?php

namespace App\Household;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Location;
use Auth;

class Outlet extends Model
{
    protected $table = "household_outlets";
    protected $fillable = [
        'ccode', 'lvl2', 'lvl3', 'lvl4', 'lvl5', 'ocode', 'oname', 'address', 'otype', 'loctype'
    ];
    
    public function outletType()
    {
       return $this->hasOne(OutletType::class,'otypeid','otype');
    }

    public static function getOutletLoc($loclvl2,$loclvl3,$loclvl4,$loclvl5,$ccode)
    {
	   $loc = Location::select('locname')
                        ->where('loclvl2',$loclvl2)
                        ->where('loclvl3',$loclvl3)
                        ->where('loclvl4',$loclvl4)
                        ->where('loclvl5',$loclvl5)
                        ->where('ccode',$ccode)->first();
	   return $loc;
    }

    public function products()
    {
       return $this->hasManyThrough(Product::class,Mapping::class,'pcode','pcode','pcode','pcode');
    }

    public function quotations()
    {
       return $this->hasMany(Quotation::class,'oid','id');
    }

    public function mappings()
    {
        return $this->hasMany(Mapping::class,'oid','id');
    }

    public function getMappings($id) {
        return Mapping::where('oid',$id)->get();
    }

    public function getFreqMappings($id,$freq) {
        return Mapping::leftJoin('household_products','household_products.pcode','=','household_mappings.pcode')
                        ->where('oid',$id)
                        ->where('household_products.survperiod',$freq)
                        ->get();
    }
}
