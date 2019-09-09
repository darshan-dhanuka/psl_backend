<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
//use JWTAuth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\PayloadFactory;
use Tymon\JWTAuth\JWTManager as JWT;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{   

    public function register(Request $request)
    {
        //dd($request);
        //die;
        $validator = Validator::make($request->json()->all() , [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6', 
          
            'phone' => 'required|max:10', 
            'uname' => 'required|unique:users', 
        ]);

        if($validator->fails()){
                return response()->json($validator->errors(), 422 );
        }

        $user = User::create([
            'name' => $request->json()->get('name'),
            'email' => $request->json()->get('email'),
            'password' => Hash::make($request->json()->get('password')),
            
            'phone' => $request->json()->get('phone'),
            'uname' => $request->json()->get('uname'),
            'referral_code' => $request->json()->get('referral_code'),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user','token'),201);
    }
    
    public function login(Request $request)
    {
        $credentials = $request->json()->all();
        //var_dump($credentials);
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json( compact('token') );
    }

    

    public function getAuthenticatedUser()
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }
        return response()->json(compact('user'));
    }

    public function forgetpw(Request $request)
    {
        error_reporting(E_ALL ^ E_NOTICE);
        $resp = array();
        $credentials = $request->json()->all();
        $mobile_number = $credentials['phone'];
        //dd($mobile_number);
        $apiKey = urlencode('hMkQfydUC6M-JRvPew5uwgT75vdyitJKmfztDmvSgN');

        $sel_qry = DB::select('SELECT * FROM users WHERE phone  = ? ', [$mobile_number]);
        $num_rows = count($sel_qry);

        if($num_rows > 0)
        {
            // $resp['errorcode'] = 0;
             // Message details
        $otp = rand(100000,999999);
        //$numbers = array(919773486995);
        $sender = urlencode('TXTLCL');
        $message = rawurlencode('This is your otp - '.$otp.' .Please put this to verify');

        $numbers = $mobile_number;

        // Prepare data for POST request
        $data = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);

        // Send the POST request with cURL
       /* $ch = curl_init('https://api.textlocal.in/send/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $content_mod = json_decode($response,true);

        if(trim($content_mod['status']) == 'success'){*/
            $ins_qry = DB::insert('insert into tbl_otp_verify  (number,otp) values (?, ?)', [$numbers, $otp]);
            if($ins_qry){
                $resp['errorcode'] = 0;
                $resp['msg'] = 'Message sent successfully';
            }else{
                $resp['errorcode'] = 1;
                $resp['msg'] = 'Message failed';
            }
       /* }else{
            $resp['errorcode'] = 1;
            $resp['msg'] = 'Message failed';
        }*/
        return json_encode($resp);

         }
         else{
             $resp['errorcode'] = 2;
             $resp['msg'] = 'Mobile_number does not exist!!';
             echo json_encode($resp);die;
         }

        
    }


    public function verify_otp(Request $request)
     {
         $credentials = $request->json()->all();
         $mobile_number = $credentials['phone1'];
         $otp = $credentials['otp_text'];

         $sel_qry = DB::select('SELECT otp FROM  tbl_otp_verify WHERE  
                        number = ? ORDER BY id DESC limit 1', [$mobile_number,$otp] );
         //dd($sel_qry);
         $otp_db = $sel_qry[0]->otp;

         //$num_row = count($sel_qry);
         if($otp_db == $otp){
             $resp['errorcode'] = 0;
             $resp['msg'] = 'Otp Valid';
         }else{
             $resp['errorcode'] = 1;
             $resp['msg'] = 'Invalid Otp';
         }

         return json_encode($resp);
     }

     public function reset_password(Request $request)
     {
         $credentials = $request->json()->all();
         $password = Hash::make($credentials['password']);
         $mobile_num = $credentials['phone1'];

         $sel_qry = DB::update('UPDATE users SET password = ? WHERE phone = ?', [$password,$mobile_num]);
         //dd($sel_qry);
         if($sel_qry){
             $resp['errorcode'] = 0;
             $resp['msg'] = 'Otp Valid';
         }else{
             $resp['errorcode'] = 1;
             $resp['msg'] = 'Invalid Otp';
         }

         echo json_encode($resp);
     }

}
