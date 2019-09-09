<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class SocialController extends Controller
{
    //
    public function socialogin(Request $request)
    {
        //$credentials = $request->json()->all();
        $email = $request->json()->get('email');
        $name = $request->json()->get('name');
        $token = $request->json()->get('id');
        //var_dump($credentials);
       // $check = DB::select('select id from users where email = :email_id', ['email_id' => $email]);
        $check = DB::select('select id from users where email = ?', [$email]);
        $count = count($check);
        if($count > 0)
        {
            //dd($check);
            return $check;
        }
        else
        {
            $insert = DB::insert('insert into users (email, name,google_token ) VALUES (?, ?,?)', [$email, $name,$token]); 
            return $check;
        }
        
    } 
}
