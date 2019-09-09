<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\State;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class StateCityController extends Controller
{
    //
    public function getStates()
    {
        
       // $states = State::all();
        //return State::all();
        //$results = State::with('states')->get();
        //return $results->toArray();
        return  $states = DB::select('select id,name from states'); 
    } 

    public function getCities($id)
    {
         // $states = DB::select('select id,name from states'); 
          return $cities = DB::select('select id,name from cities where state_id = :state_id', ['state_id' => $id]);
    } 
}
