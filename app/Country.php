<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Location;

class Country extends Model
{
    protected $table = "lib_countries";
    protected $fillable = ['loclvl2','loclvl3','loclvl4','loclvl5'];
    public $timestamps = false;
    public function locations()
    {
        return $this->hasMany('App\Location','ccode','ccode');
    }
}
