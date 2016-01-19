<?php

class AccountController extends \BaseController {

	public function __construct()
	{

		//not sure
		$token = Request::header('X-Auth-Token');
		$this->user = json_decode(AuthToken::validate($token));
		$this->api_token = Input::get('api_token');
		//end not sure

		$this->page = Request::Input('page');
		$this->per_page = Request::Input('per_page');

		$this->ofset = 0;

    	if ( is_null($this->page) || empty($this->page) ){
    		$this->page = 1;
    	}

    	if ( is_null($this->per_page) || empty($this->per_page) ){
    		$this->per_page = 20;
    	}

    	if ( $this->page != 1){
    		$this->ofset = (($this->page-1)*$this->per_page);
    	}

    	$this->users = array();
	}

	public function followerIds($id) {
		$followers = Relationship::where('follower_id', $id)
			->get(array('following_id'));
		$a = array();

		foreach($followers as $follower)
		{
		    $a[] =  $follower->following_id;
		}

		return Response::json(array("ids" => $a));
	}

	public function auth2() {

		$params = Input::all();

		$username = ltrim($params['username'], '0');
		$password = $params['password'];

		if( Auth::attempt(['username' => $username, 'password' => $params['password']]) ||
			Auth::attempt(['email' => $username, 'password' => $params['password']]) ||
			Auth::attempt(['phone' =>$username, 'password' => $params['password']])){

			$rules = array('username' => 'required', 'password' => 'required|min:6');
	$validator = Validator::make($params, $rules);

	if ($validator->fails())
	{
		return Response::json(array("status" => "0","message" => "Password should has more than or equals to 6 digits"));
	}
			
//$field = Validator::make(array('email' => $username, array('email' => 'email'))->passes()) ? 'email' : 'username';
	//echo $field;
			$user = Account::where('email', $params['username'])
		->orWhere('username', $params['username'])
		->orWhere('phone', ltrim($params['username'], '0'))
		->first();

			if(isset($user)) {

				$user->birthday;
		$user->gender;
		$user->avatar;
		$user->cover;



				$result = array(
					"status"=>"1",
					"token"=>$serializedToken,
					"user"=>$user);
				return Response::json($result);

			}
		}


	}

	public function relationList($id) {

$accounts = Relationship::where('following_id', $id)
						->where('follower_id','!=', $id)
						->where('active', '=', '1')
						->with('FollowerAccount')
						->take($this->per_page)
						->skip($this->ofset)
						->get(array('follower_id'));

		$total_account = Relationship::where('following_id', $id)
		->where('follower_id','!=', $id)
		->where('active', '=', '1')
						->count();
$followers = array();

		foreach ( $accounts as $account ){	
			$account->follower_account->birthday;
			$account->follower_account->gender;
			$account->follower_account->avatar;
			$account->follower_account->cover;
			$account->follower_account->is_following = Helpers::SK_isFollowing($account->follower_account->id,$id);
			$account->follower_account->online = true;

			if($account->follower_account->type == "user")
				array_push($followers, $account->follower_account);
		
		}

		$accounts2 = Relationship::where('follower_id', $id)
						->where('following_id','!=', $id)
						->where('active', '=', '1')
						->with('FollowingAccount')
						->take($this->per_page)
						->skip($this->ofset)
						->get(array('following_id'));

		$total_account2 = Relationship::where('follower_id', $id)
		->where('following_id','!=', $id)
		->where('active', '=', '1')
						->count();

		$followings = array();

		foreach ( $accounts2 as $account ){
			$account->following_account->birthday;
			$account->following_account->gender;
			$account->following_account->avatar;
			$account->following_account->cover;
			$account->following_account->is_following = Helpers::SK_isFollowing($account->following_account->id,$id);
			$account->following_account->online = true;
			if($account->following_account->type == "user")
			array_push($followings , $account->following_account);
		}

		$accounts3 = Relationship::where('following_id', $id)
						->where('follower_id','!=', $id)
						->where('active', '=', '1')
						->with('FollowerAccount')
						->take($this->per_page)
						->skip($this->ofset)
						->get(array('follower_id'));

		$total_account3 = Relationship::where('following_id', $id)
		->where('follower_id','!=', $id)
		->where('active', '=', '1')
						->count();

						$friends = array();
		foreach ( $accounts3 as $account ){	
			$account->follower_account->birthday;
			$account->follower_account->gender;
			$account->follower_account->avatar;
			$account->follower_account->cover;
			$account->follower_account->is_following = Helpers::SK_isFollowing($account->follower_account->id,$id);

			if($account->follower_account->type == "user" && $account->follower_account->is_following)
				array_push($friends, $account->follower_account);
		}
		//$me = Relationship::where('id', $id);


		$user = Account::find($id);
		if($user != null) {
			$user->birthday;
			$user->gender;
			$user->avatar;
			$user->cover;
		} else {
			$groupList = null;
			return Response::json(array('status' => '1',
																	//'users' => $account2,
			'count' => 
				array("me"=>1,
				"favorite"=>count($followings),
				"group"=>count($groupList),
				"friends"=>count($followers)),
			'me' => array($user),
			'favorite' => $followers,
			'group' => $groupList,
            'friends' => $followings));
		}
		
		// $me->avatar;
		// $me->cover;
		// $me->is_following;
		//$me = User::where('id', $id)->get();

		$groupListUrl = "http://api.candychat.net:1314/api/chat/group/" . $id;

		$api_response = cURL::get($groupListUrl);

		$response = $api_response->toArray();
		$response = $response['body'];

		$json = json_decode($response,true);

		$groupList = $json["content"];
		$favoriteList = null;
		$newFriendList = null;

		return Response::json(array('status' => '1',
																	//'users' => $account2,
			'count' => 
				array("me"=>1,
				"favorite"=>count($followings),
				"group"=>count($groupList),
				"friends"=>count($followers)),
			'me' => array($user),
			//			'new_friends' => $followers, 
			'favorite' => $followings,
			'group' => $groupList,
                               			'friends' => $followers,
                               			//'followers' => $followers,
                               			//'following' => $followings

                               			));
	}

	public function mentionList($id) {

$accounts = Relationship::where('following_id', $id)
						->where('follower_id','!=', $id)
						->where('active', '=', '1')
						->with('FollowerAccount')
						->take($this->per_page)
						->skip($this->ofset)
						->get(array('follower_id'));

		$total_account = Relationship::where('following_id', $id)
		->where('follower_id','!=', $id)
		->where('active', '=', '1')
						->count();

						$relations = array();
$followers = array();

		foreach ( $accounts as $account ){	
			$account->follower_account->birthday;
			$account->follower_account->gender;
			$account->follower_account->avatar;
			$account->follower_account->cover;
			$account->follower_account->is_following = Helpers::SK_isFollowing($account->follower_account->id,$id);
			$account->follower_account->online = true;

			if($account->follower_account->type == "user") {
				array_push($followers, $account->follower_account);
				//array_push($relations,$account->follower_account);
			}
			
		
		}

		$accounts2 = Relationship::where('follower_id', $id)
						->where('following_id','!=', $id)
						->where('active', '=', '1')
						->with('FollowingAccount')
						->take($this->per_page)
						->skip($this->ofset)
						->get(array('following_id'));

		$total_account2 = Relationship::where('follower_id', $id)
		->where('following_id','!=', $id)
		->where('active', '=', '1')
						->count();

		$followings = array();

		foreach ( $accounts2 as $account ){
			$account->following_account->birthday;
			$account->following_account->gender;
			$account->following_account->avatar;
			$account->following_account->cover;
			$account->following_account->is_following = Helpers::SK_isFollowing($account->following_account->id,$id);
			$account->following_account->online = true;
			if($account->following_account->type == "user") {
				array_push($followings , $account->following_account);
		array_push($relations,$account->following_account);
			}
			
		}

		$accounts3 = Relationship::where('following_id', $id)
						->where('follower_id','!=', $id)
						->where('active', '=', '1')
						->with('FollowerAccount')
						->take($this->per_page)
						->skip($this->ofset)
						->get(array('follower_id'));

		$total_account3 = Relationship::where('following_id', $id)
		->where('follower_id','!=', $id)
		->where('active', '=', '1')
						->count();

						$friends = array();
		foreach ( $accounts3 as $account ){	
			$account->follower_account->birthday;
			$account->follower_account->gender;
			$account->follower_account->avatar;
			$account->follower_account->cover;
			$account->follower_account->is_following = Helpers::SK_isFollowing($account->follower_account->id,$id);

			if($account->follower_account->type == "user" && $account->follower_account->is_following) {
				array_push($friends, $account->follower_account);
				//array_push($relations,$account->follower_account);
			}
		}

		
		


		return Response::json(array('status' => '1',
																	//'users' => $account2,
			//'count' => array("following"=>$total_account,"follower"=>$total_account2,"friend"=>$total_account3),
                               			'mentions' => $relations
                               			));
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function followers($id)
	{
		$accounts = Relationship::where('following_id', $id)
						->where('follower_id','!=', $id)
						->where('active', '=', '1')
						->with('FollowerAccount')
						->take($this->per_page)
						->skip($this->ofset)
						->get(array('follower_id'));

		$total_account = Relationship::where('following_id', $id)
		->where('follower_id','!=', $id)
		->where('active', '=', '1')
						->count();
		foreach ( $accounts as $account ){	
			$account->follower_account->birthday;
			$account->follower_account->gender;
			$account->follower_account->avatar;
			$account->follower_account->cover;
			$account->follower_account->is_following = Helpers::SK_isFollowing($account->follower_account->id,$id);
			$account->follower_account->online = true;

			if($account->follower_account->type == "user")
				array_push($this->users, $account->follower_account);
		}

		$response = array('status' => '1',
						'page' => intval($this->page),
						'per_page' => intval($this->per_page),
						'pages' => intval(ceil(count($this->users)/$this->per_page)),
						'total' => count($this->users),
						'users' => $this->users
						);

		return Response::json($response);
	}

	public function friends($id)
	{
		$accounts = Relationship::where('following_id', $id)
						->where('follower_id','!=', $id)
						->where('active', '=', '1')
						->with('FollowerAccount')
						->take($this->per_page)
						->skip($this->ofset)
						->get(array('follower_id'));

		$total_account = Relationship::where('following_id', $id)
		->where('follower_id','!=', $id)
		->where('active', '=', '1')
						->count();
		foreach ( $accounts as $account ){	
			$account->follower_account->birthday;
			$account->follower_account->gender;
			$account->follower_account->avatar;
			$account->follower_account->cover;
			$account->follower_account->is_following = Helpers::SK_isFollowing($account->follower_account->id,$id);

			if($account->follower_account->type == "user" && $account->follower_account->is_following)
				array_push($this->users, $account->follower_account);
		}

		$response = array('status' => '1',
						'page' => intval($this->page),
						'per_page' => intval($this->per_page),
						'pages' => intval(ceil(count($this->users)/$this->per_page)),
						'total' => count($this->users),
						'users' => $this->users
						);

		return Response::json($response);
	}

	/**
	 * Display followers from given user id.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function followings($id)
	{
		// if($this->page == 1) {
		// 	$myPerpage = $this->per_page + 1;
		// 	$myOfset = $this->ofset;
		// }
		// else {
		// 	$myPerpage = $this->per_page;
		// 	$myOfset = $this->ofset + 1;
		// }
		$accounts = Relationship::where('follower_id', $id)
						->where('following_id','!=', $id)
						->where('active', '=', '1')
						->with('FollowingAccount')
						->take($this->per_page)
						->skip($this->ofset)
						->get(array('following_id'));



		$total_account = Relationship::where('follower_id', $id)
						->where('following_id','!=', $id)
						->where('active', '=', '1')
						->with('FollowingAccount')
						->take($this->per_page)
						->skip($this->ofset)
						->get(array('following_id'))->count();

		$net_total_account = Relationship::where('follower_id', $id)
						->where('following_id','!=', $id)
						->where('active', '=', '1')

						->with('FollowingAccount')
						->get(array('following_id'))->count();

		$pad_sum = 0;
		$pad_sum_plus = 0;

		foreach ( $accounts as $account ){
			$account->following_account->birthday;
			$account->following_account->gender;
			$account->following_account->avatar;
			$account->following_account->cover;
			$account->following_account->is_following = Helpers::SK_isFollowing($account->following_account->id,$id);
			$account->following_account->online = true;
			//if($account->following_account->type == "user") {
				array_push($this->users, $account->following_account);
				
			// } else {
			// 	$pad_sum_plus++;
			// 	$pad_sum--;
			// }
		}

		$response = array('status' => '1',
						'page' => intval($this->page),
						'per_page' => intval($this->per_page),
						'pages' => intval(ceil(count($this->users)/$this->per_page)),
						'total_perpage' => count($this->users),
						'total' => $net_total_account,
						'users' => $this->users);

		return Response::json($response);
	}

	/**
	 * Display followers from given user id.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function pages($id)
	{
		$accounts = Relationship::where('follower_id', $id)
						->where('following_id','!=', $id)
						->where('active', '=', '1')
						->with('FollowingAccount')
						->take($this->per_page)
						->skip($this->ofset)
						->get(array('following_id'));

		$total_account = Relationship::where('follower_id', $id)
		->where('following_id','!=', $id)
		->where('active', '=', '1')

						->count();

		foreach ( $accounts as $account ){
			$account->following_account->birthday;
			$account->following_account->gender;
			$account->following_account->avatar;
			$account->following_account->cover;
			$account->following_account->is_following = Helpers::SK_isFollowing($account->following_account->id,$id);
			if($account->following_account->type == "page")
				array_push($this->users, $account->following_account);
		}

		$response = array('status' => '1',
						'page' => intval($this->page),
						'per_page' => intval($this->per_page),
						'pages' => intval(ceil(count($this->users)/$this->per_page)),
						'total' => count($this->users),
						'users' => $this->users);

		return Response::json($response);
	}

	/**
	 * Display friends from given user id.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function friends_deprecated($id)
	{
		$followings = Relationship::where('follower_id', $id)
						->where('following_id','!=', $id)
						->get(array('following_id'));

		$folllowings_id = array();
		foreach ($followings as $following) {
			array_push($folllowings_id, $following->following_id);
		}

		$followers = Relationship::where('following_id', $id)
						->where('follower_id','!=', $id)
						->get(array('follower_id'));

		$folllowers_id = array();
		foreach ($followers as $follower) {
			array_push($folllowers_id, $follower->follower_id);
		}

		$accounts = Relationship::whereIn('following_id', $folllowings_id)
						->where('follower_id','=', $id)
						->where('following_id','!=', $id)
						
						//->whereIn('id', $folllowers_id)
						->take($this->per_page)
						->skip($this->ofset)
						->get();
		$total_account = Relationship::whereIn('following_id', $folllowings_id)
						->where('follower_id','=', $id)
						->where('following_id','!=', $id)
						->count();

		foreach ( $accounts as $account ){
			$account->following_account->birthday;
			$account->following_account->gender;
			$account->following_account->avatar;
			$account->following_account->cover;
			if($account->following_account->type == "user")
			array_push($this->users, $account->following_account);
		}

		$response = array('status' => '1',
						'page' => intval($this->page),
						'per_page' => intval($this->per_page),
						'pages' => intval(ceil($total_account/$this->per_page)),
						'total' => intval($total_account),
						'users' => $this->users);

		return Response::json($response);
	}

	/**
	 * Display friends from given user id.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function friends_deprecated2($id)
	{
		$followings = Relationship::where('follower_id', $id)
						->where('following_id','!=', $id)
						->where('active', '=', '1')
						->get(array('following_id'));

		$folllowings_id = array();
		foreach ($followings as $following) {
			array_push($folllowings_id, $following->following_id);
		}

		$accounts = Relationship::whereIn('following_id', $folllowings_id)
						->where('follower_id','=', $id)
						->where('active', '=', '1')
						->take($this->per_page)
						->skip($this->ofset)
						->get();
		$total_account = Relationship::whereIn('following_id', $folllowings_id)
						->where('follower_id','=', $id)
						->count();

		foreach ( $accounts as $account ){
			$account->following_account->birthday;
			$account->following_account->gender;
			$account->following_account->avatar;
			$account->following_account->cover;
			$account->following_account->is_following = Helpers::SK_isFollowing($account->following_account->id,$id);
			array_push($this->users, $account->following_account);
		}

		$response = array('status' => '1',
						'page' => $this->page,
						'per_page' => $this->per_page,
						'pages' => ceil(count($this->users)/$this->per_page),
						'total' => count($this->users),
						'users' => $this->users);

		return Response::json($response);
	}

	/**
	 * Display suggestion list.
	 *
	 * @return Response
	 */
	public function follow_suggestion_deprecated()
	{
		
		$followers = DB::table('followers')
					// 502 
					//->select('follower_id', DB::raw('count(follower_id) as total'))
					->select('follower_id')
					->groupBy('follower_id')
					//->orderBy('total', 'DESC')
                    ->take(10)
                    ->get();
        
        $accounts_id = array();
        foreach ($followers as $follower){
        	array_push($accounts_id, $follower->follower_id);
        }
        
        


        $accounts = Account::whereIn('id', $accounts_id)->get();
 		$total_account = $accounts->count();
 		foreach ( $accounts as $account ){
			$account->birthday;
			$account->gender;
			$account->avatar;
			$account->cover;
			array_push($this->users, $account);
		}
		

		$response = array('status' => '1',
						'total' => $total_account,
						'users' => $this->users);

		return Response::json($response);
	}

	public function follow2($id) {

            
            if (Helpers::SK_isFollowing($id,$this->user->id)) {
                $follow = Helpers::SK_deleteFollow($id,$this->user->id);
            } else {
                $follow = Helpers::SK_registerFollow($id,$this->user->id);
            }
            
            if($follow) {
				$response = array('status' => '1',
				'message' => "Follow success", 
				'user_id'=>$id,
				'me_user_id'=>$this->user->id,
				'is_following'=>Helpers::SK_isFollowing($id,$this->user->id),
				//'debug'=>$this->user,
				);
				

				//$to_id = Helpers::getPostOwner($id);
				//$notify = Helpers::notifySocial($this->user->id,$this->user->name,$id,300,$id);
	
			}
			
			else {
				$response = array('status' => '1',
				'message' => "Unfollow success", 
				'user_id'=>$id,
				'me_user_id'=>$this->user->id,
				'is_following'=>Helpers::SK_isFollowing($id,$this->user->id),
				//'debug'=>$this->user,
				
				);
			}

			return Response::json($response);
        
	}

	public function add($myId,$id)
	{
		$params = array('following_id' => $id);

		$user = Account::find($myId);

		if($user != null) {
			$url = 'http://candychat.net/request.php?t=follow&a=follow&user_id='.$user->id."&user_pass=".$user->password;
		//echo $url;

		$api_response = cURL::post($url,$params);

		$response = $api_response->toArray();
		$response = $response['body'];

		$json = json_decode($response,true);

		if($json['status'] == 200)
			if(strpos($json['html'],'icon-ok') !== false) {
				$response = array('status' => '1',
				'message' => "Add friend success", 
				'user_id'=>$id,
				'me_user_id'=>$user->id,
				'is_following'=>Helpers::SK_isFollowing($id,$user->id),
				//'debug'=>$this->user,
				//'debug_response'=>$json
				);

				//$to_id = Helpers::getPostOwner($id);
				//$notify = Helpers::notifySocial($this->user->id,$this->user->name,$id,300,$id);
	
			}
			
			else {
				$response = array('status' => '1',
				'message' => "Unfriend success", 
				'user_id'=>$id,
				'me_user_id'=>$user->id,
				'is_following'=>Helpers::SK_isFollowing($id,$user->id),
				//'debug'=>$this->user,
				//'debug_response'=>$json
				);
			}
				
		else
			$response = array('status' => '0','message' => "Follow/Unfollow incomplete",'url'=>$url,'json'=>$json);

		return Response::json($response);
	} else {
		$response = array('status' => '0','message' => "Follow/Unfollow incomplete",'message'=>'User does not exist');
		return Response::json($response);
	}

		
	}

	public function follow($id)
	{
		$params = array('following_id' => $id);
		$url = 'http://candychat.net/request.php?t=follow&a=follow&user_id='.$this->user->id."&user_pass=".$this->user->password."&token=".$this->api_token;
		//echo $url;

		$api_response = cURL::post($url,$params);

		$response = $api_response->toArray();
		$response = $response['body'];

		$json = json_decode($response,true);


		
		if($json['status'] == 200)
			if(strpos($json['html'],'icon-ok') !== false) {
				$response = array('status' => '1',
				'message' => "Follow success", 
				'user_id'=>$id,
				'me_user_id'=>$this->user->id,
				'is_following'=>Helpers::SK_isFollowing($id,$this->user->id),
				//'debug'=>$this->user,
				//'debug_response'=>$json
				);

				//$to_id = Helpers::getPostOwner($id);
				//$notify = Helpers::notifySocial($this->user->id,$this->user->name,$id,300,$id);
	
			}
			
			else {
				$response = array('status' => '1',
				'message' => "Unfollow success", 
				'user_id'=>$id,
				'me_user_id'=>$this->user->id,
				'is_following'=>Helpers::SK_isFollowing($id,$this->user->id),
				//'debug'=>$this->user,
				//'debug_response'=>$json
				);
			}
				
		else
			$response = array('status' => '0','message' => "Follow/Unfollow incomplete",'url'=>$url,'json'=>$json);

		return Response::json($response);
	}

	

	/**
	 * Display suggestion list.
	 *
	 * @return Response
	 */
	public function follow_suggestion($userId)
	{

		$user = Account::find($userId);
		$url = "";
		
		//if($user != null)
			$url = 'http://candychat.net/request.php?t=search&a=follow-suggestions-mobile&user_id='.
			$user->id."&user_pass=".
			$user->password."&token=asdffdsa&q=a";
		
		$api_response = cURL::get($url);

		$response = $api_response->toArray();
		$response = $response['body'];

		$json = json_decode($response,true);

		
		//return Response::json($json);
		$total_account = sizeof($json["suggestion"]);

		$users = array();

		foreach($json["suggestion"] as $user) {
			$the_user = Account::find((int)$user["id"]);
			$the_user->avatar;
                            $the_user->cover;
                            $the_user->gender;
                            $the_user->birthday;
                            $the_user->is_following = Helpers::SK_isFollowing($the_user->id,$userId);

			$users[] = $the_user;
		}

		$response = array('status' => '1',
						 'total' => $total_account,
						//'url' => $url,
						'users' => $users
						);

		return Response::json($response);
	}

	


	

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
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
