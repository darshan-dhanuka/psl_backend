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
use Illuminate\Support\Facades\Auth;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserController extends Controller
{   

    public function register(Request $request)
    {
        //dd($request);
        //die;
        $validator = Validator::make($request->json()->all() , [
            'name' => 'max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6', 
            'state' => 'max:6', 
            'city' => 'max:6', 
            'phone' => 'max:10', 
            'uname' => 'required|unique:users', 
        ]);

        if($validator->fails()){
                return response()->json($validator->errors(), 422 );
        }

        $user = User::create([
            'name' => $request->json()->get('name'),
            'email' => $request->json()->get('email'),
            'password' => Hash::make($request->json()->get('password')),
            'state' => $request->json()->get('state'),
            'city' => $request->json()->get('city'),
            'address' => $request->json()->get('address'),
            'dob' =>date('Y-m-d', strtotime($request->json()->get('dob'))),
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
		$currentUser = Auth::user();
		$name = $currentUser->name;
		
		//print_r(compact('token','resp'));exit;
        return response()->json( compact('token','name') );
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
        //$otp = rand(100000,999999);
        $otp = 111111;
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
                        number = ? ORDER BY id DESC limit 1', [$mobile_number] );
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


	public function send_otp(Request $request)
    {
        error_reporting(E_ALL ^ E_NOTICE);
        $resp = array();
        $credentials = $request->json()->all();
        $mobile_number = $credentials[0];
        //dd($mobile_number);
		//exit;
        $apiKey = urlencode('hMkQfydUC6M-JRvPew5uwgT75vdyitJKmfztDmvSgN');
            // $resp['errorcode'] = 0;
             // Message details
        //$otp = rand(100000,999999);
        $otp = 111111;
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


	public function psl_register(Request $request)
    {
        DB::enableQueryLog();
		$resp = array();
        $credentials = $request->json()->all();
        $name = $credentials['name'];
        $phone = $credentials['phone'];
        $referral_code = $credentials['referral_code'];
        $email = $credentials['email'];
		$upd_qry = DB::update('UPDATE users SET phone = ?,name=?,referral_code=?  WHERE email = ?', [$phone,$name,$referral_code,$email]);
		/* $query = DB::getQueryLog();
		dd($upd_qry);
		exit; */
		if($upd_qry){
                $resp['errorcode'] = 0;
                $resp['msg'] = 'Registered successfully';
            }else{
                $resp['errorcode'] = 1;
                $resp['msg'] = 'Registered failed';
            }
       
        return json_encode($resp);
    }
    
	public function email_funct(Request $request)
	{
		// Replace sender@example.com with your "From" address.
		// This address must be verified with Amazon SES.
		$sender = 'darshan.dhanukaa@gmail.com';
		$senderName = 'Sender Name';

		// Replace recipient@example.com with a "To" address. If your account
		// is still in the sandbox, this address must be verified.
		$recipient = 'darshan.dhanukaa@gmail.com';

		// Replace smtp_username with your Amazon SES SMTP user name.
		$usernameSmtp = 'AKIARZNTZEVKSQHACKJP';

		// Replace smtp_password with your Amazon SES SMTP password.
		$passwordSmtp = 'BKgD1VqVjfO3cdZpSDfJVq2dR1lVRTFUdpjuqFb4M+Bz';

		// Specify a configuration set. If you do not want to use a configuration
		// set, comment or remove the next line.
		//$configurationSet = 'ConfigSet';

		// If you're using Amazon SES in a region other than US West (Oregon),
		// replace email-smtp.us-west-2.amazonaws.com with the Amazon SES SMTP
		// endpoint in the appropriate region.
		$host = 'email-smtp.us-east-1.amazonaws.com';
		$port = 587;

		// The subject line of the email
		$subject = 'Amazon SES test (SMTP interface accessed using PHP)';

		// The plain-text body of the email
		$bodyText =  "Email Test\r\nThis email was sent through the
			Amazon SES SMTP interface using the PHPMailer class.";

		// The HTML-formatted body of the email
		$bodyHtml = '<h1>Email Test</h1>
			<p>This email was sent through the
			<a href="https://aws.amazon.com/ses">Amazon SES</a> SMTP
			interface using the <a href="https://github.com/PHPMailer/PHPMailer">
			PHPMailer</a> class.</p>';

		$mail = new \PHPMailer(true);

		try {
			// Specify the SMTP settings.
			$mail->isSMTP();
			$mail->setFrom($sender, $senderName);
			$mail->Username   = $usernameSmtp;
			$mail->Password   = $passwordSmtp;
			$mail->Host       = $host;
			$mail->Port       = $port;
			$mail->SMTPAuth   = true;
			$mail->SMTPSecure = 'tls';
			//$mail->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);

			// Specify the message recipients.
			$mail->addAddress($recipient);
			// You can also add CC, BCC, and additional To recipients here.

			// Specify the content of the message.
			$mail->isHTML(true);
			$mail->Subject    = $subject;
			$mail->Body       = $bodyHtml;
			$mail->AltBody    = $bodyText;
			$mail->Send();
			echo "Email sent!" , PHP_EOL;
		} catch (phpmailerException $e) {
			echo "An error occurred. {$e->errorMessage()}", PHP_EOL; //Catch errors from PHPMailer.
		} catch (Exception $e) {
			echo "Email not sent. {$mail->ErrorInfo}", PHP_EOL; //Catch errors from Amazon SES.
		}

		
	}
					

}
