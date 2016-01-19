<?php

class UserController extends \BaseController {

	public function __construct()
	{

		//not sure
		$token = Request::header('X-Auth-Token');
		$this->user = json_decode(AuthToken::validate($token));
		$this->api_token = Input::get('api_token');
		//end not sure
	}
	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$params = Input::all();
		$params['captcha'] = "123456";
			$url = "http://candychat.net/request.php?t=register&mobile=1";

		if(!isset($params['password']))
			$params['password'] = "123456";

		$api_response = cURL::post($url,$params);
		Log::info('Result', $api_response->toArray());

		$response = $api_response->body;
		$json = json_decode($response,true);

		// if(!isset($_POST['phone']))
		// 	$username = $params['username'];
		// else
		// 	$username = $params['phone'];
		// $password = $params['password'];

		if ( $json["status"] == 200 && $json["user"] != null){
			//$json["user"]['avatar'] = $json["user"]['avatar_url'];
			//$json["user"]['cover'] = $json["user"]['cover_url'];

			//unset($json["user"]['avatar_url']);
			//unset($json["user"]['cover_url']);
			if(isset($params['username']))
				$credential = array('username'  => $params['username'],
								'password'  => $params['password']);
			else
				$credential = array('username'  => $params['phone'],
								'password'  => $params['password']);

			$json["user"]["id"] = (int) $json["user"]["id"];
			$json["user"]["active"] = (int) $json["user"]["id"];
			$json["user"]["avatar_id"] = (int) $json["user"]["id"];
			$json["user"]["cover_id"] = (int) $json["user"]["id"];


			// attempt to do the login
			if (Auth::attempt($credential)) {
				$authToken = AuthToken::create(Auth::user());
  				$publicToken = AuthToken::publicToken($authToken);
  				return Response::json(array('status' => '1',
                               			//'message' => $json["message"],
                               			'token' => $publicToken,
                               			'user' => $json["user"]
                               			//,'debug' => $json["api"]
                               			));
			}

		}else{
			if(isset($params["phone"]))
				return Response::json(array('status' => '0',
                               			'message' => "The phone number is already registered",
                               			'debug'=>$json));
			else if(isset($params["email"]))
				return Response::json(array('status' => '0',
                               			'message' => "The email is already registered",
                               			'debug'=>$json));
		}

		return Response::json(array('status' => '1',
			'token' => $publicToken,
                               			'user' => $json["user"],
                               		'debug' => $json));



	}

	public function requestOTP() {
		$params = Input::all();
		$mobile = $params['mobile'];
		$otp = Helpers::generateOTP();
		if(!isset($params['message']) || $params['message'] != "")
			$message = "Your OTP is: {$otp}";
		else
			$message = $params['message'];
		$res = Helpers::send_sms($mobile,$message);

		return Response::json(array('status' => '1',
                               			'response_api' => $res));
	}

	public function facebookLogin() {
		$params = Input::all();
		$user = Helpers::fbAuth($params['access_token']);
		if($user != null){
			$authToken = AuthToken::create($user['user_info']);
  			$publicToken = AuthToken::publicToken($authToken);
			return Response::json(array('status' => '1',
										'message' => 'Success Facebook Auth',
										'token' => $publicToken,
										'state' => $user['state'],
                               			'user' => $user['user_info']));
		}else{
			return Response::json(array('status' => '0',
										'message' => 'Wrong access_token',
										'state' => 'wrong_access_token',
                               			'user' => $user));
		}
	}

	/**
	 * Display the specified user by id.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		// GET api/v1/user/$id
		$user = Account::find($id);
		if($user != null) {
        	$user->avatar;
			$user->cover;
			$user->birthday;
			$user->gender;
        } else {
        	return Response::json(array('status' => '0',
								'user' => null,
								'count' => null));
        }

		$params = Input::all();

		if(isset($params['user_id']))
			$user->is_following = Helpers::SK_isFollowing($id,$params['user_id']);
		

		//$user->is_live = Helpers::isLive($user->username);
		//$user->live = "http://150.107.31.13:1935/live/".$user->username."/playlist.m3u8";

		$user->online = false;


		$count = array("post" => Helpers::SK_countPosts($id),
									 "follower" => Helpers::SK_countFollowers($id),
									 "following" => Helpers::SK_countFollowing($id),
									 "friend" => Helpers::SK_countFriends($id),
									 "love" => Helpers::SK_countPageLikes($id),
									 "group" => Helpers::SK_countGroupJoined($id),
									 //"follow_request" => Helpers::SK_countGroupJoined($id)
									 );
		
		if ( $user->count() > 0){
			return Response::json(array('status' => '1',
								'user' => $user,

								'count' => $count));
		}else{
			return Response::json(array('status' => '0',
								'message' => 'No user found'));
		}
	}

	/**
	 * Display the specified user by id.
	 *
	 * @param  int  $username
	 * @return Response
	 */
	public function showUsername($username)
	{
		// GET api/v1/user/$id
		$user = Account::where('username', $username)->get()->first();

        if($user != null) {
        	$user->avatar;
			$user->cover;
			$user->birthday;
			$user->gender;
        } else {
        	return Response::json(array('status' => '0',
								'user' => null,
								'count' => null));
        }
		

		

		//$user->is_following = Helpers::SK_isFollowing($id,$this->user->id);
		//$user->is_live = Helpers::isLive($user->username);
		//$user->live_url = "http://150.107.31.13:1935/live/".$user->username."/playlist.m3u8";
		//$user->live = "http://150.107.31.13:1935/live/".$user->username."/playlist.m3u8";

		//$user->online = false;


		$count = array("post" => Helpers::SK_countPosts($user->id),
									 "follower" => Helpers::SK_countFollowers($user->id),
									 "following" => Helpers::SK_countFollowing($user->id),
									 "friend" => Helpers::SK_countFriends($user->id),
									 "love" => Helpers::SK_countPageLikes($user->id),
									 "group" => Helpers::SK_countGroupJoined($user->id),
									 //"follow_request" => Helpers::SK_countGroupJoined($id)
									 );
		
		if ( $user->count() > 0){
			return Response::json(array('status' => '1',
								'user' => $user,

								'count' => $count));
		}else{
			return Response::json(array('status' => '0',
								'message' => 'No user found'));
		}
	}

	public function page($id) {
		$params = Input::all();
		$url = "http://www.candychat.net/request.php?t=getLovedPage&id=".$id."&mobile_api=1";
		$response = cURL::get($url);
		
		$response = $response->body;
		$json = json_decode($response,true);
		if(count($json) != 0)
			return Response::json(array('status' => '1',
																		'count' => count($json),
																		'user_id' => $id,
                               			'page' => $json
                               			));
		else 
			return Response::json(array('status' => '0',
																		'count' => count($json),
																		'user_id' => $id,
                               			'page' => $json
                               			));
	}

	public function changePassword() {
		$params = Input::all();
		$old_password = md5(Helpers::SK_secureEncode($params['old_password']));
		$new_password = md5(Helpers::SK_secureEncode($params['new_password']));
		//$hash = md5($password);
		$userId = (int) $this->user->id;
		$dbConnect = Helpers::dbConnect();

		if($old_password && $old_password != $new_password) {
			$find = mysqli_query($dbConnect, "SELECT password from accounts WHERE id = {$userId} AND password = '{$old_password}'");
			$sql_numrows = mysqli_num_rows($find);

			if($sql_numrows == 1) {
				//$sql_fetch = mysqli_fetch_assoc($sql_query);
				$res = mysqli_query($dbConnect, "UPDATE accounts SET password = '{$new_password}' WHERE id = {$userId}");

				if($res)
					return Response::json(array('status' => '1','message'=>'Success, your password is changed','user_id'=>$userId));
				else
					return Response::json(array('status' => '0','message'=>'Failed','user_id'=>$userId));
			}
			return Response::json(array('status' => '0','message'=>'Failed, more than 1 user found','user_id'=>$userId));
		
		}
		return Response::json(array('status' => '0','message'=>'Failed, new password should not be same as old password','user_id'=>$userId));
	}

	/**
	 * Display the specified by username.
	 *
	 * @param  int  $username
	 * @return Response
	 */
	public function check($username)
	{
		if(filter_var($username, FILTER_VALIDATE_EMAIL)) {
        	$user = Account::where('email', $username)->get()->first();
	    }
	    else {
	        $user = Account::where('username', $username)->get()->first();
	    }
		
		if($user != null) {
        	$user->avatar;
			$user->cover;
			$user->birthday;
			$user->gender;
			$params = Input::all();

			if(isset($params['user_id']))
				$user->is_following = Helpers::SK_isFollowing($user->id,$params['user_id']);
				return Response::json(array('status' => '1',
								'user' => $user));
        } else {
        	return Response::json(array('status' => '0',
								'user' => null,
								'count' => null));
        }
		
	
		if ( $user->count() > 0){
			return Response::json(array('status' => '1',
								'user' => $user));
		} else {
			return Response::json(array('status' => '0',
								'message' => 'No user found'));
		}
	}

	



	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$user = Account::find($id);

		if($user != null) {
        	$user->avatar;
			$user->cover;
			$user->birthday;
			$user->gender;
        } else {
        	return Response::json(array('status' => '0',
								'user' => null,
								'count' => null));
        }

		$password = $user->password;
		$url = "http://www.candychat.net/request.php?t=user&a=settings&mobile=1&user_id=" . $id . "&user_pass=" . $password;
		$params = Input::all();
		$api_response = cURL::post($url,$params);
		Log::info('Result', $api_response->toArray());

		$response = $api_response->body;
		$json["url"] = $url;
		$json["api"] = json_decode($response,true);

		if ( $json["api"]["status"] == 200 ){
			$a['status'] = '1';
			$a['message'] = 'Update profile success !';
			$a['params'] = $params;
			return Response::json($a);
		} else {
			return Response::json(array('status'=>'0','message'=>'something went wrong','params'=>$params,'debug' => $json));
		}

		

		/*
		username:manual
name:Yo Cool
about:
email:manual@gmail.com
birthday[0]:1
birthday[1]:1
birthday[2]:1990
gender:male
current_city:
hometown:
timezone:Pacific/Midway
*/
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}


}
