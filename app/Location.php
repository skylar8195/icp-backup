<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Country;

class Location extends Model
{
    protected $table = "locations";
    protected $fillable = [
        'ccode', 'loclvl2', 'loclvl3', 'loclvl4', 'loclvl5', 'loclvl', 'locname', 'capcity'
    ];
    public function country()
    {
        return $this->belongsTo('App\Country','ccode','ccode');
    }
}
