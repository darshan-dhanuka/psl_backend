<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class State extends Model
{
    //
    //protected $table = states;
    public function states()
    {
        return  $states = DB::select('select * from states'); 
    }
}
