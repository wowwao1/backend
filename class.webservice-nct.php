<?php
class WebService {
	public $msg;
	function __construct($client, $mysqli_con) {
		foreach ($GLOBALS as $key => $values) {
			$this->$key = $values;

		}
		$this->client = $client;
		$this->mysqli_con = $mysqli_con;
		$this->BlankUserArray = array('id' => '', 'first_name' => '', 'last_name' => '', 'email' => '', 'phone_code' => '', 'phone_no' => '', 
									'gender' => '', 'dob' => '', 'profile_img' => '', 'cover_img' => '', 'email_verified' => '', 
									'phone_verified' => '', 'country' => '', 'state' => '', 'city' => '', 'countryID' => '', 'stateID' => '', 
									'cityID' => '', 'age' => '','city_lat' => '', 'city_long' => '');
	}
	public function userSignUp($data = array()) {
		
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$firstname = ucfirst($firstname);
		$lastname = ucfirst($lastname);
		$phCheck = $this->db->count('tbl_users', array('phone_no' => $phonenumber));
		if($phCheck > 0)
		{
			return APIerror('Phone number already exists');
		}

		$insertArray = array('first_name' => $firstname, 'last_name' => '', /*'last_name' => $lastname,*/ 'email' => $email, 'password' => hash('sha256', $password ), 
			'phone_token' => ''/*$device_token*/, 'device' => $device, 'ipAddress' => get_ip_address(),'phone_no' => $phonenumber,'phone_code'=> $phonecode,  'phone_verified' => 'y');    
		
		$LastInsertId = $this->db->insert('tbl_users',$insertArray)->getLastInsertId();
		$this->db->insert('tbl_user_notification_settings', array('user_id' => $LastInsertId));
		
		//not doing email validation
		//$this->verifyEmailSend($LastInsertId);

		return APIsuccess('Registration successfully done. Please Login.');



	}

	public function phoneVerification($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');
   
		extract($data);
		 //$this->userCheck($user_id);
		//$phoneCheck = $this->db->pdoQuery("SELECT COUNT(id) as row_count FROM tbl_users WHERE CONCAT_WS('',phone_code,phone_no) = $phone_no")->result();
	
	
		$rand_num = generateRandom(6, 'num');
		if($verify_action == 'Add')
		{
			//$msg_string = 'Welcome To '.SITE_NM.', Your Verification Code is '.$rand_num;
			$msg_string = '[#] Welcome To '.SITE_NM.', Your Verification code is: '.$rand_num.' Rnhj0376iRD';
		}
		else
		{
			//$msg_string = 'Your '.SITE_NM.' Verification Code is '.$rand_num;
			$msg_string = '[#] Your Verification code is: '.$rand_num.' Rnhj0376iRD';
		}

		

		try
		{ 
			// Use the client to do fun stuff like send text messages!
			$this->client->messages->create(
			    // the number you'd like to send the message to
			    $phone_no,
			    array(
			        // A Twilio phone number you purchased at twilio.com/console
			        'from' => TWILIO_PHONE_NO,
			        // the body of the text message you'd like to send
			        'body' => $msg_string
			    )
			);
			return APIsuccess('Verification Code Sent', array('code' => $rand_num));
		} catch (Exception $e) {

			return APIerror("Verification SMS Error, Please Try Again Later");
			// return APIsuccess('Verification Code Sent', array('code' => $rand_num));
			//return print_r( $e->getTrace());
		} 
	}

	public function updatePhoneVerification($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$this->db->update('tbl_users', array('phone_code' => $phone_code, 'phone_no' => $phone_no, 'phone_verified' => 'y'), array('id' => $user_id));
		if(!file_exists(DIR_UPD.'post-nct/'.$user_id))
			mkdir(DIR_UPD.'post-nct/'.$user_id , 0777, true);
		if(!(file_exists(DIR_UPD."message-nct/".$user_id)))
				mkdir(DIR_UPD.'message-nct/'.$user_id , 0777, true);
		return APIsuccess('User Phone no Verified');
	}

	public function userVerifyEmailResend($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->db->update('tbl_users', array('email' => $email, 'email_verified' => 'n', 'email_code' => ''), array('id' => $user_id));
		$this->verifyEmailSend($user_id);

		return APIsuccess('Verification Email Sent');
	}

	public function userLogin($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$email = mysqli_real_escape_string($this->mysqli_con, $email);
		$userDataLoginCount = $this->db->pdoQuery("SELECT COUNT(id) AS row_count FROM tbl_users WHERE email = '".$email."' OR phone_no = '".$email."'")->result();
		$total_records = (int)$userDataLoginCount['row_count'];
		if($total_records == 0)
			return APIerror('Please Create An Account First');
		/*$userDataLogin = $this->db->select('tbl_users', array('id','isActive'), array('password' => hash('sha256', $password)), 
						" AND (CONCAT_WS('',phone_code,phone_no) = '".$email."' 
								OR CONCAT_WS('',REPLACE(phone_code,'+',''),phone_no) = '".$email."' 
								OR email = '".$email."' )")->results();*/
		$userDataLogin = $this->db->select('tbl_users', array('id','isActive'), array('password' => hash('sha256', $password)), 
						" AND (phone_no = '".$email."' OR email = '".$email."' )")->results();
		
		$rows = count($userDataLogin);
		if($rows == 1)
		{
			if($userDataLogin[0]['isActive'] == 'a')
			{
				$userData = $this->getUserData($userDataLogin[0]['id']);
				$this->db->update('tbl_users', array('phone_token' => ''), array('phone_token' => $device_token));
				$this->db->update('tbl_users', array('phone_token' => $device_token, 'device' => $device, 'ipAddress' => get_ip_address(), 'email_forgotcode' => ''), array('id' => $userDataLogin[0]['id']));
				return APIsuccess(SUC, $userData);
			}
			else
			{
				return APIerror('User Deactivated By Admin');
			}
		}
		else
		{
			return APIerror('Invalid Password');
		}
	}

	public function userLogout($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->db->update('tbl_users', array('phone_token' => '', 'device' => '','email_forgotcode' => ''), array('id' => $user_id));
		APIsuccess('Successfully Logged Out');
	}

	public function getCountryCode() {
		$getCountryCodeArray = array();
		
		/*$getCountryCodeArrayFile = json_decode(file_get_contents(SITE_INC."countryCode.json"));
		
		if(count($getCountryCodeArrayFile) == 0)
			APIerror();

		foreach ($getCountryCodeArrayFile as $fetch) {
			$temp_array = array();
			$temp_array['phone_code'] = '+' . $fetch->sPhoneCode;
			$temp_array['country_code'] = $fetch->sISOCode;
			$temp_array['country_name'] = $fetch->sName;
			array_push($getCountryCodeArray, $temp_array);
		}*/

		$getCountryCodeArrayFile = json_decode(file_get_contents("countryCode.json"));
		
		if(count($getCountryCodeArrayFile) == 0)
			APIerror();

		foreach ($getCountryCodeArrayFile as $fetch) {
			$temp_array = array();
			$temp_array['phone_code'] = '+' . $fetch->sPhoneCode;
			$temp_array['country_code'] = $fetch->sISOCode;
			$temp_array['country_name'] = $fetch->sName;
			array_push($getCountryCodeArray, $temp_array);
		}


		usort($getCountryCodeArray, 'sortByName');
		$finalarray = $getCountryCodeArray;
		return APIsuccess(SUC, $finalarray);

		//return print_r($getCountryCodeArrayFile);
	}

	public function getCountry() {
		$getCountryArray = array();
		$getCountryArrayDB = $this->db->select('tbl_country', array('CountryId', 'countryName'), array('isActive' => 'y'))->results();
		
		if(count($getCountryArrayDB) == 0)
			APIerror();

		foreach ($getCountryArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['CountryId'];
			$temp_array['name'] = $fetch['countryName'];
			array_push($getCountryArray, $temp_array);
		}
		$finalarray = $getCountryArray;
		return APIsuccess(SUC, $finalarray);
	}

	public function getState($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$getStateArray = array();
		$getStateArrayDB = $this->db->select('tbl_state', array('StateID', 'stateName'), array('CountryID' => $CountryID, 'isActive' => 'y'))->results();
		
		if(count($getStateArrayDB) == 0)
			APIerror();

		foreach ($getStateArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['StateID'];
			$temp_array['name'] = $fetch['stateName'];
			array_push($getStateArray, $temp_array);
		}
		$finalarray = $getStateArray;
		return APIsuccess(SUC, $finalarray);
	}

	public function getCity($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$getCityArray = array();
		$getCityArrayDB = $this->db->select('tbl_city', array('CityId', 'cityName','Latitude','Longitude'), array('StateID' => $StateID, 'isActive' => 'y'))->results();
		
		if(count($getCityArrayDB) == 0)
			APIerror();

		foreach ($getCityArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['CityId'];
			$temp_array['name'] = $fetch['cityName'];
			$temp_array['lat'] = $fetch['Latitude'];
			$temp_array['long'] = $fetch['Longitude'];
			array_push($getCityArray, $temp_array);
		}
		$finalarray = $getCityArray;
		return APIsuccess(SUC, $finalarray);
	}

	public function userAddPost($POSTdata = array(), $FILESdata = array()) {
		if(empty($POSTdata) && empty($FILESdata))
			APIerror('Invalid Data');

		extract($POSTdata);
		extract($FILESdata);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$post_text = (isset($post_text) && $post_text) != '' ? $post_text : '';
		$PostLastInsertId = $this->db->insert('tbl_user_post', array('user_id' => $user_id, 
																	'post_text' => $post_text, 
																	'post_privacy' => $post_type,
																	'createdDate' => date('Y-m-d H:i:s') ) )->getLastInsertId();
		if(isset($post_image) && (!empty($post_image)))
		{
			foreach ($post_image["tmp_name"] as $index => $image) {
				if(!file_exists(DIR_UPD.'post-nct/'.$user_id))
					mkdir(DIR_UPD.'post-nct/'.$user_id , 0777, true);

				$ext = strtolower(pathinfo($post_image['name'][$index], PATHINFO_EXTENSION));
				$file_upd = "post_".date('dmYHisu').md5($user_id).$index.".".$ext;
				$target_file = DIR_UPD."post-nct/".$user_id."/".$file_upd;
				move_uploaded_file($post_image["tmp_name"][$index], $target_file);
				ImageCompress($target_file, $target_file, 100);

				$imageArray['user_id'] = $user_id;
				$imageArray['post_id'] = $PostLastInsertId;
				$imageArray['image'] = $file_upd;
				$this->db->insert('tbl_user_post_image', $imageArray);
			}
		}
		return APIsuccess('Posted Successfully');
	}

	public function userAddComment($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->userCheck($post_user_id);
		$this->userBlockCheck($user_id, $post_user_id);
		$this->postCheck($post_id);
		$CmntLastInsertId = $this->db->insert('tbl_user_comment', array('user_id' => $user_id,
																		'post_id' => $post_id,
																		'comment' => $cmnt_text))->getLastInsertId();
		// $getPost = $this->db->select('tbl_user_post', array('user_id'), array('id' => $post_id))->result();
		if($user_id != $post_user_id)
			$this->userAddNotification($user_id, $post_user_id, $post_id, 'post-commented');

		$userData = $this->getUserData($user_id);

		$datetime = new DateTime(date("Y-m-d h:i a"));
		$new_timezone = new DateTimeZone($timezone);
		$datetime->setTimezone($new_timezone);
		
		$cmnt_date = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
		$cmnt_time = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
		// $total_reply = $this->countSubcomment($CmntLastInsertId);
		$total_reply = 0;
		return APIsuccess(SUC, array('id' => $CmntLastInsertId, 'comment' => $cmnt_text, 'cmnt_date' => $cmnt_date, 'cmnt_time' => $cmnt_time,'total_reply' => $total_reply,'userData' => $userData, 'reply' => array()));
	}

	public function userAddReply($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->userCheck($post_user_id);
		$this->userBlockCheck($user_id, $post_user_id);
		$this->userCheck($cmnt_user_id);
		$this->userBlockCheck($user_id, $cmnt_user_id);
		$this->postCheck($post_id);
		$this->commentCheck($cmnt_id);
		$ReplyLastInsertId = $this->db->insert('tbl_user_subcomment', array('user_id' => $user_id,
																		'post_id' => $post_id,
																		'cmnt_id' => $cmnt_id,
																		'subcomment' => $reply_text))->getLastInsertId();

		$userData = $this->getUserData($user_id);

		$datetime = new DateTime(date("Y-m-d h:i a"));
		$new_timezone = new DateTimeZone($timezone);
		$datetime->setTimezone($new_timezone);

		$reply_date = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
		$reply_time = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
		return APIsuccess(SUC, array('id' => $ReplyLastInsertId, 'reply' => $reply_text, 'reply_date' => $reply_date, 'reply_time' => $reply_time, 'userData' => $userData));
	}

	public function userDeletePost($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->postCheck($post_id);
		$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('user_id','image'), array('post_id' => $post_id))->results();
		foreach ($userPostImageArrayDB as $fetchimage) {
			if($fetchimage['image'] != '' && file_exists(DIR_UPD.'post-nct/'.$fetchimage['user_id'].'/'.$fetchimage['image']))
			{
				unlink(DIR_UPD.'post-nct/'.$fetchimage['user_id'].'/'.$fetchimage['image']);
			}
		}
		$this->db->delete('tbl_user_post', array('id' => $post_id));
		return APIsuccess('Post Deleted');
	}

	public function userDeleteComment($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->commentCheck($cmnt_id);
		$this->db->delete('tbl_user_comment', array('id' => $cmnt_id));
		return APIsuccess('Comment Deleted');
	}

	public function userDeleteSubcomment($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->subcommentCheck($reply_id);
		$this->db->delete('tbl_user_subcomment', array('id' => $reply_id));
		return APIsuccess('Reply Deleted');
	}

	public function userFollowPost($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->pageCheck($page);
		$user_id = (int)$user_id;
		$userFollowPost = array();
		$userFollowArray = array();
		$userFollowArrayDB = $this->db->select('tbl_user_follow', array('receiver_id'), array('sender_id' => $user_id))->results();

		if(count($userFollowArrayDB) == 0)
			APIerror();

		foreach($userFollowArrayDB as $fetch) {
			array_push($userFollowArray,$fetch['receiver_id']);
		}
		$offset = ($page - 1) * FETCH_LIMIT;
		$userPostArrayDB = $this->db->pdoQuery("SELECT tbp.*,tbu.first_name,tbu.last_name,tbu.profile_img,tbu.cover_img
												FROM tbl_user_post AS tbp
												LEFT JOIN tbl_users AS tbu
												ON tbp.user_id = tbu.id
												WHERE tbp.user_id IN(".implode($userFollowArray, ',').") AND tbp.isActive='y'
												AND tbp.createdDate > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND tbp.post_privacy = 'Public'
												ORDER BY tbp.id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();
		
		if(count($userPostArrayDB) == 0)
			APIerror();

		$RowDB = $this->db->pdoQuery("SELECT COUNT(tbp.id) AS row_count
												FROM tbl_user_post AS tbp
												LEFT JOIN tbl_users AS tbu
												ON tbp.user_id = tbu.id
												WHERE tbp.user_id IN(".implode($userFollowArray, ',').") AND tbp.isActive='y'
												AND tbp.createdDate > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND tbp.post_privacy = 'Public'
												ORDER BY tbp.id DESC ")->result();
		
		$total_records = (int)$RowDB['row_count'];
		$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);
		
		foreach ($userPostArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['id'];
			$temp_array['share_post_id'] = ($fetch['share_post_id'] != null) ? $fetch['share_post_id'] : '';
			$temp_array['share_user_id'] = ($fetch['share_user_id'] != null) ? $fetch['share_user_id'] : '';
			$temp_array['share_post_text'] = $fetch['share_post_text'];
			$temp_array['share_post_date'] = '';
			$temp_array['share_post_time'] = '';
			$temp_array['share_userData'] = ($temp_array['share_user_id'] != '') ? $this->getUserData($temp_array['share_user_id']) : $this->BlankUserArray;
			$temp_array['post_text'] = $fetch['post_text'];
			$temp_array['post_type'] = $fetch['post_privacy'];
			$temp_array['is_liked'] = $this->isLikedCheck($user_id, $temp_array['id']);
			if($fetch['share_post_id'] != '')
				$temp_array['is_shared'] = $this->isShareCheck($user_id, $temp_array['share_post_id']);
			else
				$temp_array['is_shared'] = $this->isShareCheck($user_id, $temp_array['id']);

			$temp_array['is_reported'] = $this->isReportCheck($user_id, $temp_array['id']);
			$temp_array['total_like'] = $this->countLike($temp_array['id']);
			$temp_array['total_comment'] = $this->countComment($temp_array['id']);

			$datetime = new DateTime($fetch['createdDate']);
			$new_timezone = new DateTimeZone($timezone);
			$datetime->setTimezone($new_timezone);
			$temp_array['post_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
			$temp_array['post_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
			
			//image
			if($temp_array['share_post_id'] != '')
			{
				$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('id','image'), array('post_id' => $temp_array['share_post_id']))->results();
				$img_user_id = $temp_array['share_user_id'];

				$selectPostDB = $this->db->select('tbl_user_post', array('createdDate'), array('id' => $temp_array['share_post_id']))->result();
				if($selectPostDB)
				{
					$datetime = new DateTime($selectPostDB['createdDate']);
					$new_timezone = new DateTimeZone($timezone);
					$datetime->setTimezone($new_timezone);
					$temp_array['share_post_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
					$temp_array['share_post_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
				}
			}
			else
			{
				$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('id','image'), array('post_id' => $temp_array['id']))->results();
				$img_user_id = $fetch['user_id'];
			}
			$temp_array['image'] = array();
			foreach ($userPostImageArrayDB as $fetchimage) {
				if($fetchimage['image'] != '' && file_exists(DIR_UPD.'post-nct/'.$img_user_id.'/'.$fetchimage['image']))
				{
					$temp_arrayImage = array();
					$temp_arrayImage['id'] = $fetchimage['id'];
					$temp_arrayImage['path'] = SITE_UPD.'post-nct/'.$img_user_id.'/'.$fetchimage['image'];
					array_push($temp_array['image'], $temp_arrayImage);
				}
			}

			$temp_array['userData'] = $this->getUserData($fetch['user_id']);
			/*$temp_array['userData'] = array();
			$temp_array['userData']['first_name'] = $fetch['first_name'];
			$temp_array['userData']['last_name'] = $fetch['last_name'];
			$temp_array['userData']['profile_img'] = $fetch['profile_img'];
			$temp_array['userData']['cover_img'] = $fetch['cover_img'];*/


			//time diff calculations


			$datetime1 = new DateTime($fetch['createdDate']);
			$datetime2 = new DateTime(date('Y-m-d H:i:s'));
			$interval = $datetime1->diff($datetime2);


			$diff = date_diff($datetime1, $datetime2);

			$post_made;

			if($diff->y != 0)
			{
				$temp_array['diff'] = $diff->y. " yr";
			}
			else if($diff->m != 0)
			{
				$temp_array['diff'] = $diff->m. " month";
			}
			else if($diff->d != 0)
			{
				$temp_array['diff'] = $diff->d * (24). " h";
			}
			else if($diff->h != 0)
			{
				$temp_array['diff'] = $diff->h. " h";
			}
			else if($diff->i != 0)
			{
				$temp_array['diff'] = $diff->i. " m";
			}
			else if($diff->i == 0)
			{
				$temp_array['diff'] = " Just now";
			}

			
			
			array_push($userFollowPost, $temp_array);
		}
		$finalarray = $userFollowPost;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
	}

	public function userFriendPost($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$this->pageCheck($page);
		$userFriendPost = array();
		$userFriendArray = array();
		$userFriendArrayDB = $this->db->pdoQuery(" SELECT sender_id,receiver_id FROM tbl_user_friend 
													WHERE $user_id IN (sender_id,receiver_id) AND status = 'Friends' ")->results();
		/*

		
		$userFriendArrayDB = $this->db->pdoQuery(" SELECT sender_id,receiver_id FROM tbl_user_friend 
													WHERE $user_id IN (sender_id,receiver_id) AND post_type = 'Private' ")->results();*/

		if(count($userFriendArrayDB) == 0)
			APIerror();

		foreach($userFriendArrayDB as $fetch) {
			if($fetch['sender_id'] != $user_id)
			{
				array_push($userFriendArray, $fetch['sender_id']);
			}
			else if($fetch['receiver_id'] != $user_id)
			{
				array_push($userFriendArray, $fetch['receiver_id']);
			}
		}
		$offset = ($page - 1) * FETCH_LIMIT;
		$userPostArrayDB = $this->db->pdoQuery("SELECT tbp.*,tbu.first_name,tbu.last_name,tbu.profile_img,tbu.cover_img
												FROM tbl_user_post AS tbp
												LEFT JOIN tbl_users AS tbu
												ON tbp.user_id = tbu.id
												WHERE tbp.user_id IN(".implode($userFriendArray, ',').") AND tbp.isActive='y' AND tbp.post_privacy = 'Private'
												AND (CASE WHEN tbp.post_privacy = 'Public' 
															THEN tbp.createddate > DATE_SUB(NOW(),INTERVAL 24 HOUR) 
															ELSE 1
													END)
												ORDER BY tbp.id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();
		
		if(count($userPostArrayDB) == 0)
			APIerror();

		$RowDB = $this->db->pdoQuery("SELECT COUNT(tbp.id) AS row_count
												FROM tbl_user_post AS tbp
												LEFT JOIN tbl_users AS tbu
												ON tbp.user_id = tbu.id
												WHERE tbp.user_id IN(".implode($userFriendArray, ',').") AND tbp.isActive='y' AND tbp.post_privacy = 'Private'
												AND (CASE WHEN tbp.post_privacy = 'Public' 
															THEN tbp.createddate > DATE_SUB(NOW(),INTERVAL 24 HOUR) 
															ELSE 1
													END)
												ORDER BY tbp.id DESC ")->result();
		
		$total_records = (int)$RowDB['row_count'];
		$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);
		
		foreach ($userPostArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['id'];
			$temp_array['share_post_id'] = ($fetch['share_post_id'] != null) ? $fetch['share_post_id'] : '';
			$temp_array['share_user_id'] = ($fetch['share_user_id'] != null) ? $fetch['share_user_id'] : '';
			$temp_array['share_post_text'] = $fetch['share_post_text'];
			$temp_array['share_post_date'] = '';
			$temp_array['share_post_time'] = '';
			$temp_array['share_userData'] = ($temp_array['share_user_id'] != '') ? $this->getUserData($temp_array['share_user_id']) : $this->BlankUserArray;
			$temp_array['post_text'] = $fetch['post_text'];
			$temp_array['post_type'] = $fetch['post_privacy'];
			$temp_array['is_liked'] = $this->isLikedCheck($user_id, $temp_array['id']);
			if($fetch['share_post_id'] != '')
				$temp_array['is_shared'] = $this->isShareCheck($user_id, $temp_array['share_post_id']);
			else
				$temp_array['is_shared'] = $this->isShareCheck($user_id, $temp_array['id']);

			$temp_array['is_reported'] = $this->isReportCheck($user_id, $temp_array['id']);
			$temp_array['total_like'] = $this->countLike($temp_array['id']);
			$temp_array['total_comment'] = $this->countComment($temp_array['id']);

			$datetime = new DateTime($fetch['createdDate']);
			$new_timezone = new DateTimeZone($timezone);
			$datetime->setTimezone($new_timezone);
			$temp_array['post_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
			$temp_array['post_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
			
			//image
			if($temp_array['share_post_id'] != '')
			{
				$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('id','image'), array('post_id' => $temp_array['share_post_id']))->results();
				$img_user_id = $temp_array['share_user_id'];

				$selectPostDB = $this->db->select('tbl_user_post', array('createdDate'), array('id' => $temp_array['share_post_id']))->result();
				if($selectPostDB)
				{
					$datetime = new DateTime($selectPostDB['createdDate']);
					$new_timezone = new DateTimeZone($timezone);
					$datetime->setTimezone($new_timezone);
					$temp_array['share_post_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
					$temp_array['share_post_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
				}
			}
			else
			{
				$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('id','image'), array('post_id' => $temp_array['id']))->results();
				$img_user_id = $fetch['user_id'];
			}
			$temp_array['image'] = array();
			foreach ($userPostImageArrayDB as $fetchimage) {
				if($fetchimage['image'] != '' && file_exists(DIR_UPD.'post-nct/'.$img_user_id.'/'.$fetchimage['image']))
				{
					$temp_arrayImage = array();
					$temp_arrayImage['id'] = $fetchimage['id'];
					$temp_arrayImage['path'] = SITE_UPD.'post-nct/'.$img_user_id.'/'.$fetchimage['image'];
					array_push($temp_array['image'], $temp_arrayImage);
				}
			}
			$temp_array['userData'] = $this->getUserData($fetch['user_id']);
			
			array_push($userFriendPost, $temp_array);
		}
		$finalarray = $userFriendPost;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
	}

	public function userNearByPost($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$this->pageCheck($page);

		$userNearByPost = array();
		$userNearByArray = array();
		$userBlockArray = array();

		$userData = $this->db->select('tbl_users', array('city_lat', 'city_long'), array('id' => $user_id))->result();
		
		$lat = (float)$userData['city_lat'];
		$long = (float)$userData['city_long'];
		
		/*if($lat == 0 || $long == 0)
			APIerror();*/
		
		//commenting the distance because not wanting the distance limits as of now.   

		$distance = (int)SEARCH_DIST;
		$userNearByArrayDB = $this->db->pdoQuery(" SELECT id, ( 6371 * acos( cos( radians($lat) ) * cos( radians( `city_lat` ) ) * cos( radians( `city_long` ) - radians($long) ) + sin( radians($lat) ) * sin( radians( `city_lat` ) ) ) ) AS distance
													FROM tbl_users
													WHERE $user_id != id
													/*HAVING distance <= $distance*/
													ORDER BY distance ASC ")->results();

		/*echo json_encode($userData);

		die;*/

		/*if(count($userNearByArrayDB) == 0)
			APIerror();*/ //Change Near By For my post
		array_push($userNearByArray, $user_id); //Change Near By For my post

		foreach($userNearByArrayDB as $fetch) {
			array_push($userNearByArray, $fetch['id']);
		}

		$userBlockArrayDB = $this->db->pdoQuery(" SELECT sender_id,receiver_id FROM tbl_user_block 
													WHERE $user_id IN (sender_id,receiver_id) ")->results();
		foreach($userBlockArrayDB as $fetch) {
			if($fetch['sender_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['sender_id']);
			}
			else if($fetch['receiver_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['receiver_id']);
			}
		}
		$userNearByArray = array_diff($userNearByArray, $userBlockArray);
		if(count($userNearByArray) == 0)
			APIerror();

		$offset = ($page - 1) * FETCH_LIMIT;


		$userPostArrayDB = $this->db->pdoQuery("SELECT tbp.*,tbu.first_name,tbu.last_name,tbu.profile_img,tbu.cover_img
												FROM tbl_user_post AS tbp
												LEFT JOIN tbl_users AS tbu
												ON tbp.user_id = tbu.id
												WHERE tbp.user_id IN(".implode($userNearByArray, ',').") AND tbp.isActive='y'
												AND tbp.createdDate > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND tbp.post_privacy = 'Public'
												ORDER BY tbp.id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();


		
		if(count($userPostArrayDB) == 0)
			APIerror();

		$RowDB = $this->db->pdoQuery("SELECT COUNT(tbp.id) AS row_count
												FROM tbl_user_post AS tbp
												LEFT JOIN tbl_users AS tbu
												ON tbp.user_id = tbu.id
												WHERE tbp.user_id IN(".implode($userNearByArray, ',').") AND tbp.isActive='y'
												AND tbp.createdDate > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND tbp.post_privacy = 'Public'
												ORDER BY tbp.id DESC ")->result();
		
		$total_records = (int)$RowDB['row_count'];
		$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);
		
		foreach ($userPostArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['id'];
			$temp_array['share_post_id'] = ($fetch['share_post_id'] != null) ? $fetch['share_post_id'] : '';
			$temp_array['share_user_id'] = ($fetch['share_user_id'] != null) ? $fetch['share_user_id'] : '';
			$temp_array['share_post_text'] = $fetch['share_post_text'];
			$temp_array['share_post_date'] = '';
			$temp_array['share_post_time'] = '';
			$temp_array['share_userData'] = ($temp_array['share_user_id'] != '') ? $this->getUserData($temp_array['share_user_id']) : $this->BlankUserArray;
			$temp_array['post_text'] = $fetch['post_text'];
			$temp_array['post_type'] = $fetch['post_privacy'];
			$temp_array['is_liked'] = $this->isLikedCheck($user_id, $temp_array['id']);
			if($fetch['share_post_id'] != '')
				$temp_array['is_shared'] = $this->isShareCheck($user_id, $temp_array['share_post_id']);
			else
				$temp_array['is_shared'] = $this->isShareCheck($user_id, $temp_array['id']);

			$temp_array['is_reported'] = $this->isReportCheck($user_id, $temp_array['id']);
			$temp_array['total_like'] = $this->countLike($temp_array['id']);
			$temp_array['total_comment'] = $this->countComment($temp_array['id']);
			
			$datetime = new DateTime($fetch['createdDate']);
			$new_timezone = new DateTimeZone($timezone);
			$datetime->setTimezone($new_timezone);
			$temp_array['post_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
			$temp_array['post_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");

			$datetime1 = new DateTime($fetch['createdDate']);
			$datetime2 = new DateTime(date('Y-m-d H:i:s'));
			$interval = $datetime1->diff($datetime2);


			$diff = date_diff($datetime1, $datetime2);

			$post_made;

			if($diff->y != 0)
			{
				$temp_array['diff'] = $diff->y. " yr";
			}
			else if($diff->m != 0)
			{
				$temp_array['diff'] = $diff->m. " month";
			}
			else if($diff->d != 0)
			{
				$temp_array['diff'] = $diff->d * (24). " h";
			}
			else if($diff->h != 0)
			{
				$temp_array['diff'] = $diff->h. " h";
			}
			else if($diff->i != 0)
			{
				$temp_array['diff'] = $diff->i. " m";
			}
			else if($diff->i == 0)
			{
				$temp_array['diff'] = " Just now";
			}
			
			//image
			if($temp_array['share_post_id'] != '')
			{
				$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('id','image'), array('post_id' => $temp_array['share_post_id']))->results();
				$img_user_id = $temp_array['share_user_id'];

				$selectPostDB = $this->db->select('tbl_user_post', array('createdDate'), array('id' => $temp_array['share_post_id']))->result();
				if($selectPostDB)
				{
					$datetime = new DateTime($selectPostDB['createdDate']);
					$new_timezone = new DateTimeZone($timezone);
					$datetime->setTimezone($new_timezone);
					$temp_array['share_post_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
					$temp_array['share_post_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
				}
			}
			else
			{
				$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('id','image'), array('post_id' => $temp_array['id']))->results();
				$img_user_id = $fetch['user_id'];
			}
			$temp_array['image'] = array();
			foreach ($userPostImageArrayDB as $fetchimage) {
				if($fetchimage['image'] != '' && file_exists(DIR_UPD.'post-nct/'.$img_user_id.'/'.$fetchimage['image']))
				{
					$temp_arrayImage = array();
					$temp_arrayImage['id'] = $fetchimage['id'];
					$temp_arrayImage['path'] = SITE_UPD.'post-nct/'.$img_user_id.'/'.$fetchimage['image'];
					array_push($temp_array['image'], $temp_arrayImage);
				}
			}
			$temp_array['userData'] = $this->getUserData($fetch['user_id']);
			
			array_push($userNearByPost, $temp_array);
		}
		$finalarray = $userNearByPost;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
	}

	public function userLikePost($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->postCheck($post_id);
		$like_check_count = $this->db->count('tbl_user_like', array('user_id' => $user_id, 'post_id' => $post_id));
		if($like_action == 'Like')
		{
			if($like_check_count > 0)
			{
				return APIerror('Post Already Liked');
			}
			else
			{
				$this->db->insert('tbl_user_like', array('user_id' => $user_id, 'post_id' => $post_id));
				$getPost = $this->db->select('tbl_user_post', array('user_id'), array('id' => $post_id))->result();
				if($user_id != $getPost['user_id'])
					$this->userAddNotification($user_id, $getPost['user_id'], $post_id, 'post-liked');
				return APIsuccess('Post Liked');
			}
		}
		else if($like_action == 'Unlike')
		{
			if($like_check_count > 0)
			{
				$this->db->delete('tbl_user_like', array('user_id' => $user_id, 'post_id' => $post_id));
				return APIsuccess('Post Unliked');
			}
			else
			{
				return APIerror('Post Already Unliked');
			}
		}
	}

	public function userRequest($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($sender_id);
		$this->userCheck($receiver_id);
		$sender_id = (int)$sender_id;
		$receiver_id = (int)$receiver_id;
		$this->userBlockCheck($sender_id, $receiver_id);
		
		//MAX FRIEND CHECK
		/*if(defined('MAX_FRIEND'))
		{
			$max_friend = (int)MAX_FRIEND;
			$count_request_check_DB = $this->db->pdoQuery("SELECT COUNT(id) AS row_count FROM tbl_user_friend
														WHERE $sender_id IN (sender_id,receiver_id)")->result();
			if($count_request_check_DB['row_count'] >= $max_friend)
				APIerror('You Have Reached Maximum Friend Limit');
		}*/

		$request_check_DB = $this->db->pdoQuery("SELECT sender_id,receiver_id,status FROM tbl_user_friend
													WHERE $sender_id IN (sender_id,receiver_id)
													AND $receiver_id IN (sender_id,receiver_id)")->result();
		if($request_action == 'Add')
		{
			if($request_check_DB)
			{
				if($request_check_DB['status'] == 'Friends')
					return APIerror('User Already Friend');
				else if($request_check_DB['sender_id'] == $sender_id && $request_check_DB['receiver_id'] == $receiver_id)
					return APIerror('Request Already Sent');
				else if($request_check_DB['sender_id'] == $receiver_id && $request_check_DB['receiver_id'] == $sender_id)
					return APIerror('Request Already Received');
			}
			else
			{
				$this->db->delete('tbl_user_follow',array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
				$this->db->insert('tbl_user_follow',array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
				$this->db->insert('tbl_user_friend', array('sender_id' => $sender_id, 'receiver_id' => $receiver_id, 'status' => 'Pending'));
				$this->userAddNotification($sender_id, $receiver_id,0, 'request-received');
				return APIsuccess('Request Sent');
			}
		}
		else if($request_action == 'Remove')
		{
			if($request_check_DB)
			{
				if($request_check_DB['status'] == 'Friends')
					return APIerror('User Already Friend');
				else if($request_check_DB['status'] == 'Pending')
				{
					$this->db->delete('tbl_user_friend', array('sender_id' => $sender_id, 'receiver_id' => $receiver_id, 'status' => 'Pending'));
					return APIsuccess('Request Deleted');
				}
			}
			else
			{
				return APIerror('Request Already Deleted');
			}
		}
		else if($request_action == 'Accept')
		{
			if($request_check_DB)
			{
				if($request_check_DB['status'] == 'Friends')
					return APIerror('User Already Friend');
				else if($request_check_DB['status'] == 'Pending')
				{
					$this->db->delete('tbl_user_follow',array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
					$this->db->insert('tbl_user_follow',array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
					$this->db->update('tbl_user_friend', array('status' => 'Friends'), array('sender_id' => $receiver_id, 'receiver_id' => $sender_id));
					$this->userAddNotification($sender_id, $receiver_id, 0,'request-accepted');
					return APIsuccess('Request Accepted');
				}
			}
			else
			{
				return APIerror('No Request Found');
			}
		}
		else if($request_action == 'Reject')
		{
			if($request_check_DB)
			{
				if($request_check_DB['status'] == 'Friends')
					return APIerror('User Already Friend');
				else if($request_check_DB['status'] == 'Pending')
				{
					$this->db->delete('tbl_user_friend', array('sender_id' => $receiver_id, 'receiver_id' => $sender_id, 'status' => 'Pending'));
					return APIsuccess('Request Rejected');
				}
			}
			else
			{
				return APIerror('No Request Found');
			}
		}
		else if($request_action == 'Unfriend')
		{
			if($request_check_DB)
			{
				if($request_check_DB['status'] == 'Friends')
				{
					$this->db->delete('tbl_user_follow',array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
					$this->db->delete('tbl_user_follow', array('sender_id' => $receiver_id, 'receiver_id' => $sender_id));

					$this->db->delete('tbl_user_friend', array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
					$this->db->delete('tbl_user_friend', array('sender_id' => $receiver_id, 'receiver_id' => $sender_id));
					return APIsuccess('User Unfriended');
				}
				else if($request_check_DB['status'] == 'Pending')
					return APIerror('User Already Unfriended');
			}
			else
			{
				return APIerror('User Already Unfriended');
			}
		}
	}

	public function userFriendRequestList($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->pageCheck($page);
		$userFriendReqArray = array();
		
		$offset = ($page - 1) * FETCH_LIMIT;
		$userFriendReqArrayDB = $this->db->select('tbl_user_friend', array('sender_id'), 
								array('receiver_id' => $user_id, 'status' => 'Pending'), 
								"ORDER BY id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();

		if(count($userFriendReqArrayDB) == 0)
			APIerror();

		$count_request = $this->db->count('tbl_user_friend', array('receiver_id' => $user_id, 'status' => 'Pending'));
		$page_count = (int)ceil($count_request / FETCH_LIMIT);

		foreach ($userFriendReqArrayDB as $fetch) {
			$temp_var = $this->getUserData($fetch['sender_id']);
			
			array_push($userFriendReqArray, $temp_var);
		}
		$finalarray = $userFriendReqArray;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $count_request);
	}

	public function userFriendList($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$this->pageCheck($page);
		$userFriendListArray = array();
		
		$offset = ($page - 1) * FETCH_LIMIT;
		$userFriendListArrayDB = $this->db->pdoQuery("SELECT sender_id,receiver_id FROM tbl_user_friend
													WHERE $user_id IN (sender_id,receiver_id)
													AND status = 'Friends'
													ORDER BY id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();

		if(count($userFriendListArrayDB) == 0)
			APIerror();

		$RowDB = $this->db->pdoQuery("SELECT COUNT(id) AS row_count FROM tbl_user_friend
													WHERE $user_id IN (sender_id,receiver_id)
													AND status = 'Friends' ")->result();
		
		$total_records = (int)$RowDB['row_count'];
		$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);

		foreach ($userFriendListArrayDB as $fetch) {
			if($fetch['sender_id'] != $user_id)
			{
				$temp_var = $this->getUserData($fetch['sender_id']);
				array_push($userFriendListArray, $temp_var);
			}
			else if($fetch['receiver_id'] != $user_id)
			{
				$temp_var = $this->getUserData($fetch['receiver_id']);
				array_push($userFriendListArray, $temp_var);
			}
		}
		$finalarray = $userFriendListArray;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
	}

	public function userCommentList($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$this->postCheck($post_id);
		$post_id = (int)$post_id;
		$userCommentListArray = array();
		$userBlockArray = array();
		$whereCond = '';
		$userBlockArrayDB = $this->db->pdoQuery(" SELECT sender_id,receiver_id FROM tbl_user_block 
													WHERE $user_id IN (sender_id,receiver_id) ")->results();
		foreach($userBlockArrayDB as $fetch) {
			if($fetch['sender_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['sender_id']);
			}
			else if($fetch['receiver_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['receiver_id']);
			}
		}
		if(count($userBlockArray) > 0)
		{
			$whereCond = "AND user_id NOT IN (".implode($userBlockArray, ',').")";
		}
		$offset = ($page - 1) * FETCH_LIMIT;
		$userCommentListArrayDB = $this->db->pdoQuery("SELECT id,user_id,comment,createdDate
												FROM tbl_user_comment
												WHERE post_id = $post_id AND isActive='y' $whereCond
												ORDER BY id ASC LIMIT " . $offset . "," . FETCH_LIMIT)->results();
		
		if(count($userCommentListArrayDB) == 0)
			APIerror();

		$RowDB = $this->db->pdoQuery("SELECT COUNT(id) AS row_count
												FROM tbl_user_comment
												WHERE post_id = $post_id AND isActive='y' $whereCond
												ORDER BY id ASC ")->result();
		
		$total_records = (int)$RowDB['row_count'];
		$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);

		foreach($userCommentListArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['id'];
			$temp_array['comment'] = $fetch['comment'];

			$datetime = new DateTime($fetch['createdDate']);
			$new_timezone = new DateTimeZone($timezone);
			$datetime->setTimezone($new_timezone);
			$temp_array['cmnt_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
			$temp_array['cmnt_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");

			$temp_array['total_reply'] = $this->countSubcomment($fetch['id']);
			$temp_array['userData'] = $this->getUserData($fetch['user_id']);
			$userReplyListArrayDB = $this->db->pdoQuery("SELECT id,user_id,subcomment,createdDate
												FROM tbl_user_subcomment
												WHERE cmnt_id = ".$fetch['id']." AND isActive='y' $whereCond
												ORDER BY id ASC LIMIT 1")->results();
			$temp_array['reply'] = array();
			foreach($userReplyListArrayDB as $fetchrepl) {
				$temp_array2 = array();
				$temp_array2['id'] = $fetchrepl['id'];
				$temp_array2['reply'] = $fetchrepl['subcomment'];
				$temp_array2['reply_date'] = DateFormat($fetchrepl['createdDate'], "Date");
				$temp_array2['reply_time'] = DateFormat($fetchrepl['createdDate'], "Time");
				$temp_array2['userData'] = $this->getUserData($fetchrepl['user_id']);
				array_push($temp_array['reply'], $temp_array2);
			}
			array_push($userCommentListArray, $temp_array);
		}
		$finalarray = $userCommentListArray;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
	}

	public function userReplyList($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$this->commentCheck($cmnt_id);
		$cmnt_id = (int)$cmnt_id;
		$userReplyListArray = array();
		$userBlockArray = array();
		$whereCond = '';
		$userBlockArrayDB = $this->db->pdoQuery(" SELECT sender_id,receiver_id FROM tbl_user_block 
													WHERE $user_id IN (sender_id,receiver_id) ")->results();
		foreach($userBlockArrayDB as $fetch) {
			if($fetch['sender_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['sender_id']);
			}
			else if($fetch['receiver_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['receiver_id']);
			}
		}
		if(count($userBlockArray) > 0)
		{
			$whereCond = "AND user_id NOT IN (".implode($userBlockArray, ',').")";
		}
		$offset = ($page - 1) * FETCH_LIMIT;
		$userReplyListArrayDB = $this->db->pdoQuery("SELECT id,user_id,subcomment,createdDate
												FROM tbl_user_subcomment
												WHERE cmnt_id = $cmnt_id AND isActive='y' $whereCond
												ORDER BY id ASC LIMIT " . $offset . "," . FETCH_LIMIT)->results();
		
		if(count($userReplyListArrayDB) == 0)
			APIerror();

		$RowDB = $this->db->pdoQuery("SELECT COUNT(id) AS row_count
												FROM tbl_user_subcomment
												WHERE cmnt_id = $cmnt_id AND isActive='y' $whereCond
												ORDER BY id ASC ")->result();
		
		$total_records = (int)$RowDB['row_count'];
		$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);

		foreach($userReplyListArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['id'];
			$temp_array['reply'] = $fetch['subcomment'];

			$datetime = new DateTime($fetch['createdDate']);
			$new_timezone = new DateTimeZone($timezone);
			$datetime->setTimezone($new_timezone);
			$temp_array['reply_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
			$temp_array['reply_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");

			$temp_array['userData'] = $this->getUserData($fetch['user_id']);
			array_push($userReplyListArray, $temp_array);
		}
		$finalarray = $userReplyListArray;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
	}

	public function userSearch($data = array(), $call_type = 'web') {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$this->pageCheck($page);
		$userSearchList = array();
		$userSearchArray = array();
		$userBlockArray = array();
		$userPostSearchDB = array();
		$offset = ($page - 1) * FETCH_LIMIT;
		if(ChkVar($keyword) && ChkVar($lat) && ChkVar($long) && $lat > 0 && $long > 0)
		{
			$lat = (float)$lat;
			$long = (float)$long;
			$distance = (int)SEARCH_DIST;
			$userPostSearchDB = $this->db->pdoQuery(" SELECT id, ( 6371 * acos( cos( radians($lat) ) * cos( radians( `city_lat` ) ) * cos( radians( `city_long` ) - radians($long) ) + sin( radians($lat) ) * sin( radians( `city_lat` ) ) ) ) AS distance
														FROM tbl_users
														WHERE $user_id != id
														AND (first_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%' 
														OR last_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%' 
														OR email LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%')
														HAVING distance <= $distance
														ORDER BY distance ASC ")->results();
														// LIMIT " . $offset . "," . FETCH_LIMIT)->results();

			$RowDB = $this->db->pdoQuery("SELECT COUNT(id) AS row_count, ( 6371 * acos( cos( radians($lat) ) * cos( radians( `city_lat` ) ) * cos( radians( `city_long` ) - radians($long) ) + sin( radians($lat) ) * sin( radians( `city_lat` ) ) ) ) AS distance
														FROM tbl_users
														WHERE $user_id != id
														AND (first_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%' 
														OR last_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%' 
														OR email LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%')
														HAVING distance <= $distance
														ORDER BY distance ASC  ")->result();
		
			$total_records = (int)$RowDB['row_count'];
			$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);
		}
		else if (ChkVar($keyword))
		{
			$userPostSearchDB = $this->db->pdoQuery(" SELECT id
														FROM tbl_users
														WHERE $user_id != id 
														AND (first_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%' 
														OR last_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%' 
														OR email LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%')
														ORDER BY first_name,last_name ASC ")->results();
														// LIMIT " . $offset . "," . FETCH_LIMIT)->results();
			
			$RowDB = $this->db->pdoQuery("SELECT COUNT(id) AS row_count
														FROM tbl_users
														WHERE $user_id != id 
														AND (first_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%' 
														OR last_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%' 
														OR email LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%')
														ORDER BY first_name,last_name ASC  ")->result();
		
			$total_records = (int)$RowDB['row_count'];
			$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);
		}
		else if (ChkVar($lat) && ChkVar($long) && $lat > 0 && $long > 0)
		{
			$lat = (float)$lat;
			$long = (float)$long;
			$distance = (int)SEARCH_DIST;
			$userPostSearchDB = $this->db->pdoQuery(" SELECT id, ( 6371 * acos( cos( radians($lat) ) * cos( radians( `city_lat` ) ) * cos( radians( `city_long` ) - radians($long) ) + sin( radians($lat) ) * sin( radians( `city_lat` ) ) ) ) AS distance
														FROM tbl_users
														WHERE $user_id != id
														HAVING distance <= $distance
														ORDER BY distance ASC ")->results();
														// LIMIT " . $offset . "," . FETCH_LIMIT)->results();
			
			$RowDB = $this->db->pdoQuery("SELECT COUNT(id) AS row_count, ( 6371 * acos( cos( radians($lat) ) * cos( radians( `city_lat` ) ) * cos( radians( `city_long` ) - radians($long) ) + sin( radians($lat) ) * sin( radians( `city_lat` ) ) ) ) AS distance
														FROM tbl_users
														WHERE $user_id != id
														HAVING distance <= $distance
														ORDER BY distance ASC")->result();
		
			$total_records = (int)$RowDB['row_count'];
			$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);
		}
		else
		{
			APIerror('Invalid Data');
		}
		
		if(count($userPostSearchDB) == 0 && $call_type == 'web')
			APIerror();
		else if(count($userPostSearchDB) == 0 && $call_type == 'msg')
			APIerror();
		else if(count($userPostSearchDB) == 0 && $call_type != 'web')
			return 'error';

		foreach($userPostSearchDB as $fetch) {
			array_push($userSearchArray, $fetch['id']);
		}

		$userBlockArrayDB = $this->db->pdoQuery(" SELECT sender_id,receiver_id FROM tbl_user_block 
													WHERE $user_id IN (sender_id,receiver_id) ")->results();
		foreach($userBlockArrayDB as $fetch) {
			if($fetch['sender_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['sender_id']);
			}
			else if($fetch['receiver_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['receiver_id']);
			}
		}
		$userSearchArray = array_diff($userSearchArray, $userBlockArray);
		if(count($userSearchArray) == 0)
			APIerror();

		foreach ($userSearchArray as $fetch) {
			$temp_array = $this->getUserData($fetch);
			$temp_array['is_friend'] = $this->userFriendCheck($user_id, $fetch);
			$temp_array['is_follow'] = $this->userFollowCheck($user_id, $fetch);
			array_push($userSearchList, $temp_array);
		}
		$finalarray = array();
		if($call_type == 'web')
		{
			$finalarray['user'] = $userSearchList;
			$finalarray['post'] = array();
			return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
		}
		else if($call_type == 'msg')
		{
			$finalarray = $userSearchList;
			return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
		}
		else
		{
			$finalarray = $userSearchList;
			return $finalarray;
		}
	}

	public function postSearch($data = array(), $call_type = 'web') {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$this->pageCheck($page);
		$postSearchArray = array();
		$userSearchArray = array();
		$userBlockArray = array();
		$whereCond = '';
		if (ChkVar($lat) && ChkVar($long) && $lat > 0 && $long > 0)
		{
			$lat = (float)$lat;
			$long = (float)$long;
			$distance = (int)SEARCH_DIST;
			$userPostSearchDB = $this->db->pdoQuery(" SELECT id, ( 6371 * acos( cos( radians($lat) ) * cos( radians( `city_lat` ) ) * cos( radians( `city_long` ) - radians($long) ) + sin( radians($lat) ) * sin( radians( `city_lat` ) ) ) ) AS distance
														FROM tbl_users
														WHERE $user_id != id
														HAVING distance <= $distance
														ORDER BY distance ASC ")->results();
			foreach($userPostSearchDB as $fetch) {
				array_push($userSearchArray, $fetch['id']);
			}

			if(count($userPostSearchDB) == 0 && $call_type == 'web')
				APIerror();
			else if(count($userPostSearchDB) == 0 && $call_type != 'web')
				return 'error';
		}
		else if(ChkVar($keyword))
		{
			$keyword = $keyword;
		}
		else
		{
			APIerror('Invalid Data');
		}
		/*if(count($userPostSearchDB) == 0)
			APIerror();*/

		$userBlockArrayDB = $this->db->pdoQuery(" SELECT sender_id,receiver_id FROM tbl_user_block 
													WHERE $user_id IN (sender_id,receiver_id) ")->results();
		foreach($userBlockArrayDB as $fetch) {
			if($fetch['sender_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['sender_id']);
			}
			else if($fetch['receiver_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['receiver_id']);
			}
		}
		$userSearchArray = array_diff($userSearchArray, $userBlockArray);
		/*if(count($userSearchArray) == 0)
			APIerror();*/
		if(ChkVar($keyword))
		{
			$whereCond .= " AND (tbp.post_text LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%' OR tbp.share_post_text LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%')";
		}
		if(count($userSearchArray) > 0)
		{
			$whereCond .= " AND tbp.user_id IN(".implode($userSearchArray, ',').")";
		}
		if(count($userBlockArray) > 0)
		{
			$whereCond .= " AND tbp.user_id NOT IN(".implode($userBlockArray, ',').")";
		}

		$offset = ($page - 1) * FETCH_LIMIT;
		$userPostArrayDB = $this->db->pdoQuery("SELECT tbp.*,tbu.first_name,tbu.last_name,tbu.profile_img,tbu.cover_img
												FROM tbl_user_post AS tbp
												LEFT JOIN tbl_users AS tbu
												ON tbp.user_id = tbu.id
												WHERE tbp.isActive='y' $whereCond
												AND tbp.createdDate > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND tbp.post_privacy = 'Public'
												ORDER BY tbp.id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();
												// ORDER BY tbp.id DESC ")->results();
		
		if(count($userPostArrayDB) == 0 && $call_type == 'web')
			APIerror();
		else if(count($userPostArrayDB) == 0 && $call_type != 'web')
			return 'error';

		$RowDB = $this->db->pdoQuery("SELECT COUNT(tbp.id) AS row_count
												FROM tbl_user_post AS tbp
												LEFT JOIN tbl_users AS tbu
												ON tbp.user_id = tbu.id
												WHERE tbp.isActive='y' $whereCond
												AND tbp.createdDate > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND tbp.post_privacy = 'Public'
												ORDER BY tbp.id DESC ")->result();
		
		$total_records = (int)$RowDB['row_count'];
		$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);
		
		foreach ($userPostArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['id'];
			$temp_array['share_post_id'] = ($fetch['share_post_id'] != null) ? $fetch['share_post_id'] : '';
			$temp_array['share_user_id'] = ($fetch['share_user_id'] != null) ? $fetch['share_user_id'] : '';
			$temp_array['share_post_text'] = $fetch['share_post_text'];
			$temp_array['share_post_date'] = '';
			$temp_array['share_post_time'] = '';
			$temp_array['share_userData'] = ($temp_array['share_user_id'] != '') ? $this->getUserData($temp_array['share_user_id']) : $this->BlankUserArray;
			$temp_array['post_text'] = $fetch['post_text'];
			$temp_array['post_type'] = $fetch['post_privacy'];
			$temp_array['is_liked'] = $this->isLikedCheck($user_id, $temp_array['id']);
			if($fetch['share_post_id'] != '')
				$temp_array['is_shared'] = $this->isShareCheck($user_id, $temp_array['share_post_id']);
			else
				$temp_array['is_shared'] = $this->isShareCheck($user_id, $temp_array['id']);

			$temp_array['is_reported'] = $this->isReportCheck($user_id, $temp_array['id']);
			$temp_array['total_like'] = $this->countLike($temp_array['id']);
			$temp_array['total_comment'] = $this->countComment($temp_array['id']);
			
			$datetime = new DateTime($fetch['createdDate']);
			$new_timezone = new DateTimeZone($timezone);
			$datetime->setTimezone($new_timezone);
			$temp_array['post_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
			$temp_array['post_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
			
			//image
			if($temp_array['share_post_id'] != '')
			{
				$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('id','image'), array('post_id' => $temp_array['share_post_id']))->results();
				$img_user_id = $temp_array['share_user_id'];

				$selectPostDB = $this->db->select('tbl_user_post', array('createdDate'), array('id' => $temp_array['share_post_id']))->result();
				if($selectPostDB)
				{
					$datetime = new DateTime($selectPostDB['createdDate']);
					$new_timezone = new DateTimeZone($timezone);
					$datetime->setTimezone($new_timezone);
					$temp_array['share_post_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
					$temp_array['share_post_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
				}
			}
			else
			{
				$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('id','image'), array('post_id' => $temp_array['id']))->results();
				$img_user_id = $fetch['user_id'];
			}
			$temp_array['image'] = array();
			foreach ($userPostImageArrayDB as $fetchimage) {
				if($fetchimage['image'] != '' && file_exists(DIR_UPD.'post-nct/'.$img_user_id.'/'.$fetchimage['image']))
				{
					$temp_arrayImage = array();
					$temp_arrayImage['id'] = $fetchimage['id'];
					$temp_arrayImage['path'] = SITE_UPD.'post-nct/'.$img_user_id.'/'.$fetchimage['image'];
					array_push($temp_array['image'], $temp_arrayImage);
				}
			}
			$temp_array['userData'] = $this->getUserData($fetch['user_id']);
			
			array_push($postSearchArray, $temp_array);
		}
		$finalarray = array();
		if($call_type == 'web')
		{
			$finalarray['user'] = array();
			$finalarray['post'] = $postSearchArray;
			return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
		}
		else
		{
			$finalarray = $postSearchArray;
			return array("current_page" => $page,"total_page" => $page_count,"total_records" => $total_records, "data" => $finalarray);
		}
	}

	public function bothSearch($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$this->pageCheck($page);

		$data['page'] = 1;
		$userSearchArray = $this->userSearch($data, 'internal');
		
		$data['page'] = $page;
		$postSearchArray = $this->postSearch($data, 'internal');
		if($postSearchArray == 'error')
		{
			$postSearchArray = array();
			$total_records = 0;
			$page_count = 0;
		}
		else
		{
			$total_records = $postSearchArray['total_records'];
			$page_count = $postSearchArray['total_page'];
			$postSearchArray = $postSearchArray['data'];
		}

		if($userSearchArray == 'error')
			$userSearchArray = array();

		$finalarray = array();
		$finalarray['user'] = $userSearchArray;
		$finalarray['post'] = $postSearchArray;
		if(empty($finalarray['user']) && empty($finalarray['post']))
			return APIerror();
		else
			return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
	}

	public function userBlockList($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->pageCheck($page);
		$userBlockArray = array();
		
		$offset = ($page - 1) * FETCH_LIMIT;
		$userBlockArrayDB = $this->db->select('tbl_user_block', array('receiver_id'), array('sender_id' => $user_id), 
								"ORDER BY id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();

		if(count($userBlockArrayDB) == 0)
			APIerror();

		$count_request = $this->db->count('tbl_user_block', array('sender_id' => $user_id));
		$page_count = (int)ceil($count_request / FETCH_LIMIT);

		foreach ($userBlockArrayDB as $fetch) {
			$temp_var = $this->getUserData($fetch['receiver_id']);
			
			array_push($userBlockArray, $temp_var);
		}
		$finalarray = $userBlockArray;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $count_request);
	}

	public function userBlock($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($sender_id);
		$this->userCheck($receiver_id);
		if($sender_id == $receiver_id)
			APIerror('User Can Not Be Blocked');

		if($block_action == 'Block')
		{
			$this->userBlockCheck($sender_id, $receiver_id, 'o');
			$this->db->delete('tbl_user_friend',array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
			$this->db->delete('tbl_user_friend',array('sender_id' => $receiver_id, 'receiver_id' => $sender_id));
			$this->db->delete('tbl_user_follow',array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
			$this->db->delete('tbl_user_follow',array('sender_id' => $receiver_id, 'receiver_id' => $sender_id));
			$this->db->insert('tbl_user_block',array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
			$this->db->pdoQuery("DELETE FROM tbl_user_post WHERE ? IN (share_user_id,user_id) AND ? IN (share_user_id,user_id)", 
				array($sender_id,$receiver_id));

			return APIsuccess('User Blocked');
		}
		else
		{
			$this->db->delete('tbl_user_block',array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
			// $this->db->delete('tbl_user_block',array('sender_id' => $receiver_id, 'receiver_id' => $sender_id));
			return APIsuccess('User Unblocked');
		}
	}

	public function userFollowList($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->pageCheck($page);
		$userFollowArray = array();
		
		$offset = ($page - 1) * FETCH_LIMIT;
		$userFollowArrayDB = $this->db->select('tbl_user_follow', array('receiver_id'), array('sender_id' => $user_id), 
								"ORDER BY id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();

		if(count($userFollowArrayDB) == 0)
			APIerror();

		$count_request = $this->db->count('tbl_user_follow', array('sender_id' => $user_id));
		$page_count = (int)ceil($count_request / FETCH_LIMIT);

		foreach ($userFollowArrayDB as $fetch) {
			$temp_var = $this->getUserData($fetch['receiver_id']);
			
			array_push($userFollowArray, $temp_var);
		}
		$finalarray = $userFollowArray;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $count_request);
	}

	public function userFollow($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($sender_id);
		$this->userCheck($receiver_id);
		$this->userBlockCheck($sender_id, $receiver_id);
		$count_request = $this->db->count('tbl_user_follow', array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
		if($follow_action == 'Follow')
		{
			if($count_request > 0)
				return APIerror('User Already Followed');
			else
			{
				$this->db->insert('tbl_user_follow', array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
				$this->userAddNotification($sender_id, $receiver_id, 0, 'user-followed');
				return APIsuccess('User Followed');
			}
		}
		else
		{
			if($count_request > 0)
			{
				$this->db->delete('tbl_user_follow',array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
				return APIsuccess('User Unfollowed');
			}
			else
				return APIerror('User Already Unfollowed');
		}
	}

	public function userPost($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($sender_id);
		$this->userCheck($receiver_id);
		$sender_id = (int)$sender_id;
		$receiver_id = (int)$receiver_id;
		$userPostArray = array();
		$offset = ($page - 1) * FETCH_LIMIT;
		$whereCond = '';
		if($sender_id == $receiver_id)
		{
			$whereCond .= " AND (tbp.post_privacy = 'Private' OR tbp.post_privacy = 'Public')";
		}
		else
		{
			$this->userBlockCheck($sender_id, $receiver_id);
			$is_friend = $this->userFriendCheck($sender_id, $receiver_id);
			if($is_friend == 'y')
			{
				$whereCond .= " AND (tbp.post_privacy = 'Private' OR tbp.post_privacy = 'Public')";
			}
			else
			{
				$whereCond .= " AND tbp.post_privacy = 'Public'";
			}
		}
		$userPostArrayDB = $this->db->pdoQuery("SELECT tbp.*,tbu.first_name,tbu.last_name,tbu.profile_img,tbu.cover_img
												FROM tbl_user_post AS tbp
												LEFT JOIN tbl_users AS tbu
												ON tbp.user_id = tbu.id
												WHERE tbp.isActive='y' AND tbp.user_id = $receiver_id $whereCond
												AND (CASE WHEN tbp.post_privacy = 'Public' 
															THEN tbp.createddate > DATE_SUB(NOW(),INTERVAL 24 HOUR) 
															ELSE 1
													END)
												ORDER BY tbp.id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();
		
		if(count($userPostArrayDB) == 0)
			APIerror();

		$RowDB = $this->db->pdoQuery("SELECT COUNT(tbp.id) AS row_count
												FROM tbl_user_post AS tbp
												LEFT JOIN tbl_users AS tbu
												ON tbp.user_id = tbu.id
												WHERE tbp.isActive='y' AND tbp.user_id = $receiver_id $whereCond
												AND (CASE WHEN tbp.post_privacy = 'Public' 
															THEN tbp.createddate > DATE_SUB(NOW(),INTERVAL 24 HOUR) 
															ELSE 1
													END)
												ORDER BY tbp.id DESC ")->result();
		
		$total_records = (int)$RowDB['row_count'];
		$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);
		foreach ($userPostArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['id'];
			$temp_array['share_post_id'] = ($fetch['share_post_id'] != null) ? $fetch['share_post_id'] : '';
			$temp_array['share_user_id'] = ($fetch['share_user_id'] != null) ? $fetch['share_user_id'] : '';
			$temp_array['share_post_text'] = $fetch['share_post_text'];
			$temp_array['share_post_date'] = '';
			$temp_array['share_post_time'] = '';
			$temp_array['share_userData'] = ($temp_array['share_user_id'] != '') ? $this->getUserData($temp_array['share_user_id']) : $this->BlankUserArray;
			$temp_array['post_text'] = $fetch['post_text'];
			$temp_array['post_type'] = $fetch['post_privacy'];
			$temp_array['is_liked'] = $this->isLikedCheck($sender_id, $temp_array['id']);
			if($fetch['share_post_id'] != '')
				$temp_array['is_shared'] = $this->isShareCheck($sender_id, $temp_array['share_post_id']);
			else
				$temp_array['is_shared'] = $this->isShareCheck($sender_id, $temp_array['id']);

			$temp_array['is_reported'] = $this->isReportCheck($sender_id, $temp_array['id']);
			$temp_array['total_like'] = $this->countLike($temp_array['id']);
			$temp_array['total_comment'] = $this->countComment($temp_array['id']);
			
			$datetime = new DateTime($fetch['createdDate']);
			$new_timezone = new DateTimeZone($timezone);
			$datetime->setTimezone($new_timezone);
			$temp_array['post_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
			$temp_array['post_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
			
			//image
			if($temp_array['share_post_id'] != '')
			{
				$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('id','image'), array('post_id' => $temp_array['share_post_id']))->results();
				$img_user_id = $temp_array['share_user_id'];

				$selectPostDB = $this->db->select('tbl_user_post', array('createdDate'), array('id' => $temp_array['share_post_id']))->result();
				if($selectPostDB)
				{
					$datetime = new DateTime($selectPostDB['createdDate']);
					$new_timezone = new DateTimeZone($timezone);
					$datetime->setTimezone($new_timezone);
					$temp_array['share_post_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
					$temp_array['share_post_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
				}
			}
			else
			{
				$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('id','image'), array('post_id' => $temp_array['id']))->results();
				$img_user_id = $fetch['user_id'];
			}
			$temp_array['image'] = array();
			foreach ($userPostImageArrayDB as $fetchimage) {
				if($fetchimage['image'] != '' && file_exists(DIR_UPD.'post-nct/'.$img_user_id.'/'.$fetchimage['image']))
				{
					$temp_arrayImage = array();
					$temp_arrayImage['id'] = $fetchimage['id'];
					$temp_arrayImage['path'] = SITE_UPD.'post-nct/'.$img_user_id.'/'.$fetchimage['image'];
					array_push($temp_array['image'], $temp_arrayImage);
				}
			}
			$temp_array['userData'] = $this->getUserData($fetch['user_id']);
			
			array_push($userPostArray, $temp_array);
		}
		$finalarray = $userPostArray;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
	}

	public function userSharePost($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->postCheck($post_id);
		/*$is_shared = $this->isShareCheck($user_id, $post_id);
		if($is_shared == 'y')
			APIerror('Post Already Shared');*/

		if(!(ChkVar($share_post_text)))
		{
			$share_post_text = '';
		}
		$selectPostDB = $this->db->select('tbl_user_post', array('id','user_id','post_text','post_privacy'), array('id' => $post_id))->result();
		$insertArray = array('share_post_id' => $selectPostDB['id'], 
							'share_user_id' => $selectPostDB['user_id'], 
							'share_post_text' => $share_post_text, 
							'user_id' => $user_id, 
							'post_text' => $selectPostDB['post_text'], 
							'post_privacy' => $post_type,
						     'createdDate' => date('Y-m-d H:i:s'));
		$this->db->insert('tbl_user_post', $insertArray);
		
		return APIsuccess('Post Shared');
	}

	public function userProfile($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($sender_id);
		$this->userCheck($receiver_id);
		$this->userBlockCheck($sender_id, $receiver_id);
		$userData = $this->getUserData($receiver_id);
		if($sender_id != $receiver_id)
		{
			$userData['is_friend'] = $this->userFriendCheck($sender_id, $receiver_id);
			$userData['is_follow'] = $this->userFollowCheck($sender_id, $receiver_id);
		}
		else
		{
			$userData['is_friend'] = '';
			$userData['is_follow'] = '';
		}
		$userData['total_follower'] = $this->db->count('tbl_user_follow', array('receiver_id' => $receiver_id));
		
		$userData['total_posts'] = $this->db->count('tbl_user_post', array('user_id' => $receiver_id));

		$RowDB = $this->db->pdoQuery("SELECT COUNT(id) AS row_count FROM tbl_user_friend
			where sender_id = $receiver_id OR  receiver_id = $sender_id")->result();

		$userData['total_friends'] = (int)$RowDB['row_count'];

		return APIsuccess(SUC, $userData);
	}

	public function userSendMessage($POSTdata = array(), $FILESdata = array()) {
		if(empty($POSTdata) && empty($FILESdata))
			APIerror('Invalid Data');

		extract($POSTdata);
		extract($FILESdata);
		$this->userCheck($sender_id);
		$this->userCheck($receiver_id);
		$this->userBlockCheck($sender_id, $receiver_id);
		$sender_id = (int)$sender_id;
		$receiver_id = (int)$receiver_id;
		$message = (isset($message) && $message) != '' ? $message : '';
		$target_file = '';
		$MsgLastInsertId = $this->db->insert('tbl_user_message', array('sender_id' => $sender_id, 
																	'receiver_id' => $receiver_id, 
																	'message' => $message))->getLastInsertId();
		if(isset($msg_attachment) && (!empty($msg_attachment)))
		{
				if(!(file_exists(DIR_UPD."message-nct/".$sender_id)))
					mkdir(DIR_UPD.'message-nct/'.$sender_id , 0777, true);

				$ext = strtolower(pathinfo($msg_attachment['name'], PATHINFO_EXTENSION));
				if($ext == 'php' || $ext == 'bat' || $ext == 'js' || $ext == 'exe' || $ext == 'deb')
				{
					$this->db->delete('tbl_user_message', array('id' => $MsgLastInsertId));
					return APIerror('Executable Files Not Allowed To Upload');
				}
				else
				{
					$file_upd = "attachment_".date('dmYHisu').md5($sender_id).".".$ext;
					$target_file = DIR_UPD."message-nct/".$sender_id."/".$file_upd;
					move_uploaded_file($msg_attachment["tmp_name"], $target_file);

					$attachmentArray = array();
					$attachmentArray['user_id'] = $sender_id;
					$attachmentArray['msg_id'] = $MsgLastInsertId;
					$attachmentArray['attachment'] = $file_upd;
					$this->db->insert('tbl_user_message_attachment', $attachmentArray);
				}
		}
		$userData = $this->getUserData($sender_id);

		$datetime = new DateTime(date("Y-m-d h:i a"));
		$new_timezone = new DateTimeZone($timezone);
		$datetime->setTimezone($new_timezone);
		$msg_date = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
		$msg_time = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
		$attachment = ($target_file != '') ? SITE_UPD."message-nct/".$sender_id."/".$file_upd : '' ;
		
		$this->userAddNotification($sender_id, $receiver_id, 0,'user-message');

		return APIsuccess(SUC, array('id' => $MsgLastInsertId, 'sender_id' => $sender_id, 'receiver_id' => $receiver_id, 'message' => $message, 
									'attachment' => $attachment, 'msg_date' => $msg_date, 'msg_time' => $msg_time));
	}

	public function userConversation($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$user_id = (int)$user_id;
		$this->userCheck($user_id);
		$this->pageCheck($page);
		$conversationListArray = array();
		$offset = ($page - 1) * FETCH_LIMIT;
		$MsgArrayDB = $this->db->pdoQuery("SELECT *FROM tbl_user_message 
										WHERE id IN (SELECT MAX(id) FROM tbl_user_message 
										WHERE ((sender_id = $user_id AND send_copy = 'y') OR (receiver_id = $user_id AND rece_copy = 'y')) 
										GROUP BY GREATEST(receiver_id,sender_id), LEAST(receiver_id, sender_id))
										AND ((sender_id = $user_id AND send_copy = 'y') OR (receiver_id = $user_id AND rece_copy = 'y')) 
										ORDER BY id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();
		if(count($MsgArrayDB) == 0)
			APIerror();

		$RowDB = $this->db->pdoQuery("SELECT COUNT(id) AS row_count
										FROM tbl_user_message 
										WHERE id IN (SELECT MAX(id) FROM tbl_user_message 
										WHERE ((sender_id = $user_id AND send_copy = 'y') OR (receiver_id = $user_id AND rece_copy = 'y')) 
										GROUP BY GREATEST(receiver_id,sender_id), LEAST(receiver_id, sender_id))
										AND ((sender_id = $user_id AND send_copy = 'y') OR (receiver_id = $user_id AND rece_copy = 'y'))")->result();
		
		$total_records = (int)$RowDB['row_count'];
		$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);

		foreach ($MsgArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['id'];
			$temp_array['message'] = $fetch['message'];
			$temp_array['attachment'] = '';
			$attachmentDB = $this->db->select('tbl_user_message_attachment', array('user_id','attachment'), array('msg_id' => $temp_array['id']))->result();
			if($attachmentDB && file_exists(DIR_UPD.'message-nct/'.$attachmentDB['user_id'].'/'.$attachmentDB['attachment']))
			{
				$temp_array['attachment'] = SITE_URL.'download-nct/'.$attachmentDB['user_id'].'/'.$attachmentDB['attachment'];
				// $temp_array['attachment'] = SITE_UPD.'message-nct/'.$attachmentDB['user_id'].'/'.$attachmentDB['attachment'];
			}
			$temp_array['read_status'] = 'Read';
			if($fetch['receiver_id'] == $user_id && $fetch['read_status'] == 'Unread')
			{
				$temp_array['read_status'] = $fetch['read_status'];
			}

			$datetime = new DateTime($fetch['createdDate']);
			$new_timezone = new DateTimeZone($timezone);
			$datetime->setTimezone($new_timezone);
			$temp_array['msg_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
			$temp_array['msg_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
			
			if($fetch['sender_id'] != $user_id)
			{
				$temp_array['read_count'] = $this->db->count('tbl_user_message', array('sender_id' => $fetch['sender_id'], 'receiver_id' => $user_id, 'read_status' => 'Unread'));
				$temp_array['userData'] = $this->getUserData($fetch['sender_id']);
			}
			else
			{
				$temp_array['read_count'] = 0;
				$temp_array['userData'] = $this->getUserData($fetch['receiver_id']);
			}
			
			array_push($conversationListArray, $temp_array);
		}
		$finalarray = $conversationListArray;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
	}

	public function userMessageList($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($sender_id);
		$this->userCheck($receiver_id);
		$this->userBlockCheck($sender_id, $receiver_id);
		$this->pageCheck($page);
		$MessageListArray = array();
		$sender_id = (int)$sender_id;
		$receiver_id = (int)$receiver_id;
		$offset = ($page - 1) * FETCH_LIMIT;
		$MsgArrayDB = $this->db->pdoQuery("SELECT *FROM tbl_user_message 
										WHERE $sender_id IN (sender_id,receiver_id)
										AND $receiver_id IN (sender_id,receiver_id)
										AND ((sender_id = $sender_id AND send_copy = 'y') OR (receiver_id = $sender_id AND rece_copy = 'y'))
										ORDER BY id ASC ")->results();
										// ORDER BY id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();
		if(count($MsgArrayDB) == 0)
			APIerror();

		$RowDB = $this->db->pdoQuery("SELECT COUNT(id) AS row_count
									FROM tbl_user_message 
									WHERE $sender_id IN (sender_id,receiver_id)
									AND $receiver_id IN (sender_id,receiver_id)
									AND ((sender_id = $sender_id AND send_copy = 'y') OR (receiver_id = $sender_id AND rece_copy = 'y'))")->result();
		
		$total_records = (int)$RowDB['row_count'];
		$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);

		foreach ($MsgArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['id'];
			$temp_array['sender_id'] = $fetch['sender_id'];
			$temp_array['receiver_id'] = $fetch['receiver_id'];
			$temp_array['message'] = $fetch['message'];
			$temp_array['attachment'] = '';
			$attachmentDB = $this->db->select('tbl_user_message_attachment', array('user_id','attachment'), array('msg_id' => $temp_array['id']))->result();
			if($attachmentDB && file_exists(DIR_UPD.'message-nct/'.$attachmentDB['user_id'].'/'.$attachmentDB['attachment']))
			{
				// $temp_array['attachment'] = SITE_UPD.'message-nct/'.$attachmentDB['user_id'].'/'.$attachmentDB['attachment'];
				$temp_array['attachment'] = SITE_URL.'download-nct/'.$attachmentDB['user_id'].'/'.$attachmentDB['attachment'];
			}
			/*if($fetch['receiver_id'] == $sender_id && $fetch['read_status'] == 'Unread')
			{
				$temp_array['read_status'] = $fetch['read_status'];
			}*/
			$datetime = new DateTime($fetch['createdDate']);
			$new_timezone = new DateTimeZone($timezone);
			$datetime->setTimezone($new_timezone);
			$temp_array['msg_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
			$temp_array['msg_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
			
			array_push($MessageListArray, $temp_array);
		}
		$this->db->update('tbl_user_message', array('read_status' => 'Read'), array( 'sender_id' => $receiver_id, 'receiver_id' => $sender_id));
		$finalarray = $MessageListArray;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
	}

	public function userMessageDelete($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($sender_id);
		$this->userCheck($receiver_id);
		$sender_id = (int)$sender_id;
		$receiver_id = (int)$receiver_id;
		$this->db->update('tbl_user_message', array('send_copy' => 'n'), array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
		$this->db->update('tbl_user_message', array('rece_copy' => 'n'), array('sender_id' => $receiver_id, 'receiver_id' => $sender_id));
		$deleteMessageDB = $this->db->pdoQuery("SELECT tbuma.* FROM tbl_user_message_attachment AS tbuma
												LEFT JOIN tbl_user_message AS tbum
												ON tbum.id = tbuma.msg_id
												WHERE tbum.send_copy = 'n' AND tbum.rece_copy = 'n' AND $sender_id IN (sender_id,receiver_id)
												AND $receiver_id IN (sender_id,receiver_id) ")->results();
		if(count($deleteMessageDB) > 0)
		{
			foreach ($deleteMessageDB as $fetch) {
				if($fetch['attachment'] != '' && file_exists(DIR_UPD.'message-nct/'.$fetch['user_id'].'/'.$fetch['attachment']))
				{
					unlink(DIR_UPD.'message-nct/'.$fetch['user_id'].'/'.$fetch['attachment']);
				}
			}
		}
		$whereCond = " AND $sender_id IN (sender_id,receiver_id) AND $receiver_id IN (sender_id,receiver_id) ";
		$this->db->delete('tbl_user_message', array('send_copy' => 'n', 'rece_copy' => 'n'), $whereCond);
		return APIsuccess('Conversation Deleted');
	}

	public function userEditProfile($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		if(ChkVar($dob))
			$dob = date('d-m-Y' ,strtotime($dob));
		else
			$dob = '';

		if(ChkVar($gender) && ($gender == 'Male' || $gender == 'Female'))
			$gender = $gender;
		else
			$gender = '';


		if($phone_code == "")
		{
			$this->db->update('tbl_users', array('first_name' => $first_name, 'last_name' => '' /*$lastname */, 'dob' =>'' /*$dob*/, 'gender' => ''/*$gender*/, 'city' => $city, 
											'state' => $state, 'country' => $country, 'city_lat' => $city_lat, 'city_long' => $city_long, 'about'=> $about, 'email'=>$email), 
											array('id' => $user_id));

			return APIsuccess('Profile Updated');
		}
		else
		{
			$this->db->update('tbl_users', array('first_name' => $firstname, 'last_name' => '', 'phone_code' => $phone_code,
		       'phone_no' => $phone_no,  'gender' => $gender, 'about'=> $about,'email'=>$email, 'dob'=>'', 'city'=> $city, 'state'=>$state, 'country'=>$country, 'city_lat' => $city_lat, 'city_long' => $city_long), 
											array('id' => $user_id));

			return APIsuccess('Profile Updated');

		}

		/*$this->db->update('tbl_users', array('first_name' => $firstname, 'last_name' => $lastname, 'dob' => $dob, 'gender' => $gender, 'city' => $city, 
											'state' => $state, 'country' => $country, 'city_lat' => $city_lat, 'city_long' => $city_long), 
											array('id' => $user_id));
		return APIsuccess('Profile Updated');*/
	}

	public function userEditPicture($POSTdata = array(), $FILESdata = array()) {
		if(empty($POSTdata) && empty($FILESdata))
			APIerror('Invalid Data');

		extract($POSTdata);
		extract($FILESdata);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;

		if($picture_type == 'Profile' && isset($image_upload) && (!empty($image_upload)))
		{
				$ext = strtolower(pathinfo($image_upload['name'], PATHINFO_EXTENSION));
				$file_upd = "profile_".date('dmYHisu').md5($user_id).".".$ext;
				
				$target_file = DIR_UPD."profile-nct/".$file_upd;
				
				
				
				move_uploaded_file($image_upload["tmp_name"], $target_file);
				ImageCompress($target_file, $target_file, 100);

				$userProfileDB = $this->db->select('tbl_users', array('profile_img'), array('id' => $user_id))->result();
				if($userProfileDB && $userProfileDB['profile_img'] != '' && file_exists(DIR_UPD."profile-nct/".$userProfileDB['profile_img']))
				{
					unlink(DIR_UPD."profile-nct/".$userProfileDB['profile_img']);
				}

				$this->db->update('tbl_users', array('profile_img' => $file_upd), array('id' => $user_id));

				//$this->db->update('tbl_users', array('profile_img' => "xxxx"), array('id' => $user_id));



		}
		else if($picture_type == 'Cover' && isset($image_upload) && (!empty($image_upload)))
		{
				$ext = strtolower(pathinfo($image_upload['name'], PATHINFO_EXTENSION));
				$file_upd = "cover_".date('dmYHisu').md5($user_id).".".$ext;
				$target_file = DIR_UPD."profile-nct/".$file_upd;
				move_uploaded_file($image_upload["tmp_name"], $target_file);
				ImageCompress($target_file, $target_file, 100, 'cover');

				$userProfileDB = $this->db->select('tbl_users', array('cover_img'), array('id' => $user_id))->result();
				if($userProfileDB && $userProfileDB['cover_img'] != '' && file_exists(DIR_UPD."profile-nct/".$userProfileDB['cover_img']))
				{
					unlink(DIR_UPD."profile-nct/".$userProfileDB['cover_img']);
				}

				$this->db->update('tbl_users', array('cover_img' => $file_upd), array('id' => $user_id));
		}
		else
		{
			return APIerror('Invalid Data');
		}
		return APIsuccess('Profile Updated');
	}



	public function usereditPost($POSTdata = array(), $FILESdata = array()) {
		if(empty($POSTdata) && empty($FILESdata))
			APIerror('Invalid Data');

		extract($POSTdata);
		extract($FILESdata);
		$this->userCheck($user_id);
		$this->postCheck($post_id);
		$user_id = (int)$user_id;
		$post_id = (int)$post_id;
		$post_text = (isset($post_text) && $post_text) != '' ? $post_text : '';
		$PostLastInsertId = $post_id;
		$this->db->update('tbl_user_post', array('post_text' => $post_text, 'post_privacy' => $post_type), array('id' => $post_id));
		if(isset($post_image) && (!empty($post_image)))
		{
			foreach ($post_image["tmp_name"] as $index => $image) {
				if(!file_exists(DIR_UPD.'post-nct/'.$user_id))
					mkdir(DIR_UPD.'post-nct/'.$user_id , 0777, true);

				$ext = strtolower(pathinfo($post_image['name'][$index], PATHINFO_EXTENSION));
				$file_upd = "post_".date('dmYHisu').md5($user_id).$index.".".$ext;
				$target_file = DIR_UPD."post-nct/".$user_id."/".$file_upd;
				move_uploaded_file($post_image["tmp_name"][$index], $target_file);
				ImageCompress($target_file, $target_file, 100);

				$imageArray['user_id'] = $user_id;
				$imageArray['post_id'] = $PostLastInsertId;
				$imageArray['image'] = $file_upd;
				$this->db->insert('tbl_user_post_image', $imageArray);
			}
		}
		return APIsuccess('Post Edited');
	}

	public function userDeletePostImage($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$postImageDB = $this->db->select('tbl_user_post_image', array('user_id','image'), array('id' => $img_id))->result();
		$this->db->delete('tbl_user_post_image', array('id' => $img_id));
		if($postImageDB && $postImageDB['image'] != '' && file_exists(DIR_UPD.'post-nct/'.$postImageDB['user_id'].'/'.$postImageDB['image']))
		{
			unlink(DIR_UPD.'post-nct/'.$postImageDB['user_id'].'/'.$postImageDB['image']);
			return APIsuccess('Post Image Deleted');
		}
		else
		{
			return APIerror('Post Image Already Deleted');
		}
	}

	public function userNotificationList($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->pageCheck($page);
		$userNotificationArray = array();
		$user_id = (int)$user_id;

		$offset = ($page - 1) * FETCH_LIMIT;
		$userNotificationArrayDB = $this->db->select('tbl_user_notification', '*', array('receiver_id' => $user_id), 
								"ORDER BY id DESC LIMIT " . $offset . "," . FETCH_LIMIT)->results();
		
		if(count($userNotificationArrayDB) == 0)
			APIerror();

		$RowDB = $this->db->count('tbl_user_notification', array('receiver_id' => $user_id));
		
		$total_records = (int)$RowDB;
		$page_count = (int)ceil($RowDB / FETCH_LIMIT);

		foreach ($userNotificationArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['id'];
			$temp_array['userData'] = $this->getUserData($fetch['sender_id']);
			$temp_array['noti_type'] = $fetch['noti_type'];
			$temp_array['createddate'] = $fetch['createdDate'];
			$temp_array['post_id'] = $fetch['post_id'];

			if($temp_array['noti_type'] == 'post-commented')
				$temp_array['noti_message'] = $temp_array['userData']['first_name'].' Comment On Your Post';
			else if($temp_array['noti_type'] == 'post-liked')
				$temp_array['noti_message'] = $temp_array['userData']['first_name'].' Liked Your Post';
			else if($temp_array['noti_type'] == 'request-received')
				$temp_array['noti_message'] = $temp_array['userData']['first_name'].' Sent You Request';
			else if($temp_array['noti_type'] == 'request-accepted')
				$temp_array['noti_message'] = $temp_array['userData']['first_name'].' Accepted Your Request';
			else if($temp_array['noti_type'] == 'user-followed')
				$temp_array['noti_message'] = $temp_array['userData']['first_name'].' Started Following You';
			
			$temp_array['read_status'] = $fetch['read_status'];
			array_push($userNotificationArray, $temp_array);
		}
		$this->db->update('tbl_user_notification', array('read_status' => 'Read'), array('receiver_id' => $user_id));
		$finalarray = $userNotificationArray;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);

		
	}

	public function userNotificationSettings($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->db->update('tbl_user_notification_settings', array('post_cmnt' => $post_cmnt, 
																'post_like' => $post_like, 
																'req_rece' => $req_rece, 
																'req_acpt' => $req_acpt, 
																'user_follow' => $user_follow,
																'user_msg' => $user_msg), 
															array('user_id' => $user_id));
		return APIsuccess('Notification Settings Updated');
	}

	public function userChangePassword($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$passwordCheck = $this->db->count('tbl_users', array('password' => hash('sha256', $old_password), 'id' => $user_id));
		if($passwordCheck > 0)
		{
			$this->db->update('tbl_users', array('password' => hash('sha256', $new_password)), array('id' => $user_id));
			return APIsuccess('Password Changed Successfully');
		}
		else
		{
			return APIerror('Wrong Old Password');
		}
	}

	public function userReportPost($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->userCheck($post_user_id);
		$this->postCheck($post_id);
		$user_id = (int)$user_id;
		$post_id = (int)$post_id;
		$post_user_id = (int)$post_user_id;
		$ReportCheck = $this->db->count('tbl_user_reported_post', array('user_id' => $user_id, 'post_id' => $post_id));
		if($ReportCheck == 0)
		{
			$this->db->insert('tbl_user_reported_post', array('user_id' => $user_id, 'post_id' => $post_id));
			$this->userAddNotification($user_id, $post_user_id, 0, 'post-reported');
			return APIsuccess('Post Reported Successfully');
		}
		else
		{
			return APIerror('Post Already Reported By You');
		}
	}

	public function userContactUs($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->db->insert('tbl_contact_us', array('firstName' => $firstname, 'lastName' => $lastname, 'email' => $email, 'subject' => $subject, 
												'message' => $message, 'ipAddress' => get_ip_address()));
		return APIsuccess('Your Query Submitted');
	}

	public function userDeactive($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$userCheckCount = $this->db->select('tbl_users', array('id', 'profile_img', 'cover_img'),
									array('id' => $user_id, 'password' => hash('sha256', $password)))->result();
		if($userCheckCount)
		{
			if($userCheckCount['profile_img'] != '' && file_exists(DIR_UPD.'profile-nct/'.$userCheckCount['profile_img']))
				unlink(DIR_UPD.'profile-nct/'.$userCheckCount['profile_img']);

			if($userCheckCount['cover_img'] != '' && file_exists(DIR_UPD.'profile-nct/'.$userCheckCount['cover_img']))
				unlink(DIR_UPD.'profile-nct/'.$userCheckCount['cover_img']);
			
			if(file_exists(DIR_UPD.'message-nct/'.$user_id))
				delete_directory(DIR_UPD.'message-nct/'.$user_id);
			
			if(file_exists(DIR_UPD.'post-nct/'.$user_id))
				delete_directory(DIR_UPD.'post-nct/'.$user_id);

			$this->db->delete('tbl_users',array('id' => $user_id));
			
			return APIsuccess('Your Account Successfully Deactivated');
		}
		else
			return APIerror('Wrong Password');
	}

	public function userForgotPassword($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);

		$userData = $this->db->select('tbl_users', array('id','first_name','last_name','email'), 
									array('email' => $email, 'email_verified' => 'n', 'phone_verified' => 'y'))->result();
		if($userData)
		{
			$VerifyLinkCode = md5(date('d-M-Y H:i').$userData['email']);
	        $VerifyLink = SITE_URL.'forgot-password/?code='.$VerifyLinkCode;
			$arrayCont = array();

			$arrayCont['verify_link'] = "<a href=".$VerifyLink." target='_blank'>Click Here</a>";
			$arrayCont['greetings'] = $userData['first_name'].' '.$userData['last_name'];

			$array = generateEmailTemplate('forgot_password',$arrayCont);
			sendEmailAddress($userData['email'], $array['subject'], $array['message']);
			$this->db->update('tbl_users', array('email_forgotcode' => $VerifyLinkCode), array('id' => $userData['id']));
			return APIsuccess('Password Reset Link Sent To Your Email');
		}
		else
		{
			return APIerror('Wrong Email Address');
		}
	}

	public function userGetNotificationSettings($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$userSettingsDB = $this->db->select('tbl_user_notification_settings', array('post_cmnt','post_like','req_rece','req_acpt','user_follow','user_msg'), 
															array('user_id' => $user_id))->result();
		if($userSettingsDB)
			return APIsuccess(SUC,$userSettingsDB);
		else
			return APIerror();
	}

	public function editSharedPost($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$this->postCheck($post_id);
		$user_id = (int)$user_id;
		$post_id = (int)$post_id;
		$share_post_text = (isset($share_post_text) && $share_post_text) != '' ? $share_post_text : '';
		$this->db->update('tbl_user_post', array('share_post_text' => $share_post_text, 'post_privacy' => $post_type), array('id' => $post_id));
		return APIsuccess('Post Edited');
	}

	public function userUpdatesCount($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$count_request = $this->db->count('tbl_user_friend', array('receiver_id' => $user_id, 'status' => 'Pending'));
		$count_notification = $this->db->count('tbl_user_notification', array('receiver_id' => $user_id, 'read_status' => 'Unread'));
		$count_message = $this->db->count('tbl_user_message', array('receiver_id' => $user_id, 'rece_copy' => 'y', 'read_status' => 'Unread'));
		
		return APIsuccess(SUC, array('count_request' => $count_request, 'count_notification' => $count_notification, 'count_message' => $count_message));
	}

	public function getAdvertisement($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$ad_id = (int)$ad_id;
		$AdvertisementArray = array();
		$AdvertisementClickDB = $this->db->pdoQuery("SELECT total_click FROM tbl_advertisement WHERE isActive = 'y' GROUP BY total_click ORDER BY total_click")->result();
		
		if(!($AdvertisementClickDB))
			APIerror();
		else
			$click = $AdvertisementClickDB['total_click'];

		$AdvertisementDB = $this->db->select('tbl_advertisement', array('id','name','image','link','description'), array('total_click' => $click,'isActive' => 'y'), " AND id NOT IN ($ad_id) ")->results();

		if(count($AdvertisementDB) == 0)
			APIerror();

		$random = array_rand($AdvertisementDB);
		
		$temp_array = array();
		$temp_array['id'] = $AdvertisementDB[$random]['id'];
		$temp_array['name'] = $AdvertisementDB[$random]['name'];
		if($AdvertisementDB[$random]['image'] != '' && file_exists(DIR_UPD.'advertisement-nct/'.$AdvertisementDB[$random]['image']))
		{
			$temp_array['image'] = SITE_UPD.'advertisement-nct/'.$AdvertisementDB[$random]['image'];
		}
		else
		{
			$temp_array['image'] = SITE_UPD.'th1_no_image.jpg';
		}
		$temp_array['link'] = $AdvertisementDB[$random]['link'];
		$temp_array['description'] = $AdvertisementDB[$random]['description'];
		// array_push($AdvertisementArray, $temp_array);
		
		$finalarray = $temp_array;

		return APIsuccess(SUC, $finalarray);
	}

	public function advertisementClick($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$ad_id = (int)$ad_id;
		$AdvertisementClickDB = $this->db->select('tbl_advertisement', array('total_click'), array('id' => $ad_id))->result();
		if($AdvertisementClickDB)
		{
			$AdvertisementClickDB['total_click'] = 1 + (int)$AdvertisementClickDB['total_click'];
			$this->db->update('tbl_advertisement', array('total_click' => $AdvertisementClickDB['total_click']), array('id' => $ad_id));
			return APIsuccess();
		}
		else
			return APIerror();
	}

	public function getStaticPagesList() {
		$StaticPagesArray = array();
		$StaticPagesArrayDB = $this->db->select('tbl_content', array('pId', 'pageTitle','pageDesc'), array('isActive' => 'y'))->results();
		
		if(count($StaticPagesArrayDB) == 0)
			APIerror();

		foreach ($StaticPagesArrayDB as $fetch) {
			$temp_array = array();
			$temp_array['id'] = $fetch['pId'];
			$temp_array['name'] = $fetch['pageTitle'];
			// $temp_array['description'] = $fetch['pageDesc'];
			array_push($StaticPagesArray, $temp_array);
		}
		$finalarray = $StaticPagesArray;
		return APIsuccess(SUC, $finalarray);
	}

	public function getSingleStaticPage($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$st_id = (int)$st_id;
		$StaticPagesArray = array();
		$StaticPagesArrayDB = $this->db->select('tbl_content', array('pId', 'pageTitle','pageDesc'), array('pId' => $st_id, 'isActive' => 'y'))->result();
		if($StaticPagesArrayDB)
		{
			$temp_array = array();
			$temp_array['id'] = $StaticPagesArrayDB['pId'];
			$temp_array['name'] = $StaticPagesArrayDB['pageTitle'];
			$temp_array['description'] = $StaticPagesArrayDB['pageDesc'];
			// array_push($StaticPagesArray, $temp_array);
			$finalarray = $temp_array;
			return APIsuccess(SUC, $finalarray);
		}
		else
			return APIerror();
	}

	public function userComposeSearch($data = array()) {
		if(empty($data))
			APIerror('Invalid Data');

		extract($data);
		$this->userCheck($user_id);
		$user_id = (int)$user_id;
		$this->pageCheck($page);
		$userSearchList = array();
		$userSearchArray = array();
		$userBlockArray = array();
		$userPostSearchDB = array();
		$offset = ($page - 1) * FETCH_LIMIT;
		
		$userPostSearchDB = $this->db->pdoQuery(" SELECT id
													FROM tbl_users
													WHERE $user_id != id 
													AND (first_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%' 
													OR last_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%')
													ORDER BY first_name,last_name ASC ")->results();
													// LIMIT " . $offset . "," . FETCH_LIMIT)->results();
		
		$RowDB = $this->db->pdoQuery("SELECT COUNT(id) AS row_count
													FROM tbl_users
													WHERE $user_id != id 
													AND (first_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%' 
													OR last_name LIKE '%".mysqli_real_escape_string($this->mysqli_con, $keyword)."%')
													ORDER BY first_name,last_name ASC  ")->result();
	
		$total_records = (int)$RowDB['row_count'];
		$page_count = (int)ceil($RowDB['row_count'] / FETCH_LIMIT);
		
		if(count($userPostSearchDB) == 0)
			APIerror();

		foreach($userPostSearchDB as $fetch) {
			array_push($userSearchArray, $fetch['id']);
		}

		$userBlockArrayDB = $this->db->pdoQuery(" SELECT sender_id,receiver_id FROM tbl_user_block 
													WHERE $user_id IN (sender_id,receiver_id) ")->results();
		foreach($userBlockArrayDB as $fetch) {
			if($fetch['sender_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['sender_id']);
			}
			else if($fetch['receiver_id'] != $user_id)
			{
				array_push($userBlockArray, $fetch['receiver_id']);
			}
		}
		$userSearchArray = array_diff($userSearchArray, $userBlockArray);
		if(count($userSearchArray) == 0)
			APIerror();

		foreach ($userSearchArray as $fetch) {
			$temp_array = $this->getUserData($fetch);
			$temp_array['first_name'] = str_ireplace($keyword, "<b>$keyword</b>", $temp_array['first_name']);
			$temp_array['last_name'] = str_ireplace($keyword, "<b>$keyword</b>", $temp_array['last_name']);
			$temp_array['is_friend'] = $this->userFriendCheck($user_id, $fetch);
			$temp_array['is_follow'] = $this->userFollowCheck($user_id, $fetch);
			array_push($userSearchList, $temp_array);
		}
		$finalarray = array();
		
		$finalarray = $userSearchList;
		return APIsuccess(SUC, $finalarray, $page, $page_count, $total_records);
	}


	// Back Ground Helpers
	public function isLikedCheck($user_id = 0, $post_id = 0) {
		if(!($user_id > 0 && $post_id > 0))
			return APIerror('Invalid Data');

		$isLiked = $this->db->count('tbl_user_like', array('user_id' => $user_id, 'post_id' => $post_id));
		$isLiked = ($isLiked > 0) ? 'y' : 'n' ;
		return $isLiked;
	}

	public function isShareCheck($user_id = 0, $post_id = 0) {
		if(!($user_id > 0 && $post_id > 0))
			return APIerror('Invalid Data');

		$isShare = $this->db->count('tbl_user_post', array('user_id' => $user_id, 'share_post_id' => $post_id));
		$isShare = ($isShare > 0) ? 'y' : 'n' ;
		return $isShare;
	}

	public function isReportCheck($user_id = 0, $post_id = 0) {
		if(!($user_id > 0 && $post_id > 0))
			return APIerror('Invalid Data');

		$isReport = $this->db->count('tbl_user_reported_post', array('user_id' => $user_id, 'post_id' => $post_id));
		$isReport = ($isReport > 0) ? 'y' : 'n' ;
		return $isReport;
	}

	public function countFollowers($id = 0) {
		if(!($id > 0))
			return APIerror('Invalid User Id');

		$followerCount = $this->db->count('tbl_user_follow', array('receiver_id' => $id));
		return $followerCount;
	}

	public function countLike($id = 0) {
		if(!($id > 0))
			return APIerror('Invalid Post Id');

		$likeCount = $this->db->count('tbl_user_like', array('post_id' => $id));
		return $likeCount;
	}

	public function countComment($id = 0) {
		if(!($id > 0))
			return APIerror('Invalid Post Id');

		$commnentCount = $this->db->count('tbl_user_comment', array('post_id' => $id));
		return $commnentCount;
	}

	public function countSubcomment($id = 0) {
		if(!($id > 0))
			return APIerror('Invalid Comment Id');

		$subcommentCount = $this->db->count('tbl_user_subcomment', array('cmnt_id' => $id));
		return $subcommentCount;
	}

	public function getUserData($id = 0) {
		if(!($id > 0))
			return APIerror('Invalid User Id');

		$userData = $this->db->select('tbl_users', array('id','first_name','last_name','email','phone_code','phone_no','gender','dob','profile_img','cover_img','email_verified','phone_verified','country','state','city','city_lat','city_long','FCM_TOKEN', 'about', 'isOnline'), array('id' => $id))->result();
		if($userData)
		{
			if($userData['profile_img'] != '' && file_exists(DIR_UPD.'profile-nct/'.$userData['profile_img']))
			{
				$userData['profile_img'] = SITE_UPD.'profile-nct/'.$userData['profile_img'];
			}
			else
			{
				$userData['profile_img'] = SITE_UPD.'no_user.png';
			}
			if($userData['cover_img'] != '' && file_exists(DIR_UPD.'profile-nct/'.$userData['cover_img']))
			{
				$userData['cover_img'] = SITE_UPD.'profile-nct/'.$userData['cover_img'];
			}
			else
			{
				$userData['cover_img'] = SITE_UPD.'no_cover.jpg';
			}
			if($userData['country'] != '')
			{
				$country = $this->db->select('tbl_country', array('countryName'), array('CountryId' => $userData['country']))->result();
				if($country)
				{
					$userData['countryID'] = $userData['country'];
					$userData['country'] = $country['countryName'];
				}
				else
				{
					$userData['countryID'] = "";
					$userData['country'] = "";
				}
			}
			else
			{
				$userData['countryID'] = "";
				$userData['country'] = "";
			}
			if($userData['state'] != '')
			{
				$state = $this->db->select('tbl_state', array('stateName'), array('StateID' => $userData['state']))->result();
				if($state)
				{
					$userData['stateID'] = $userData['state'];
					$userData['state'] = $state['stateName'];
				}
				else
				{
					$userData['stateID'] = "";
					$userData['state'] = "";
				}
			}
			else
			{
				$userData['stateID'] = "";
				$userData['state'] = "";
			}
			if($userData['city'] != '')
			{
				$city = $this->db->select('tbl_city', array('cityName'), array('CityId' => $userData['city']))->result();
				if($city)
				{
					$userData['cityID'] = $userData['city'];
					$userData['city'] = $city['cityName'];
				}
				else
				{
					$userData['cityID'] = "";
					$userData['city'] = "";
				}
			}
			else
			{
				$userData['cityID'] = "";
				$userData['city'] = "";
			}
			if($userData['dob'] != '')
			{
				$diff = date_diff(date_create($userData['dob']), date_create(date("Y-m-d")));
				$userData['dob'] = DateFormat($userData['dob'], "Date");
				$userData['age'] = $diff->format('%y');
			}
			else
			{
				$userData['age'] = "";
			}

			if(empty($userData['about'])) {
				$userData['about'] = '';
			}
		}
		else
		{
			$userData = $this->BlankUserArray;
		}
		return $userData;
	}

	public function verifyEmailSend($id = 0) {
		if(!($id > 0))
			return APIerror('Invalid User Id');

		$userData = $this->db->select('tbl_users', array('first_name','last_name','email'), array('id' => $id))->result();

		$VerifyLinkCode = md5(date('d-M-Y H:i').$userData['email']);
        $VerifyLink = SITE_URL.'verify-email/?code='.$VerifyLinkCode;
		$arrayCont = array();

		$arrayCont['verify_link'] = "<a href=".$VerifyLink." target='_blank'>Click Here</a>";
		$arrayCont['greetings'] = $userData['first_name'].' '.$userData['last_name'];

		$array = generateEmailTemplate('verifyemail',$arrayCont);
		sendEmailAddress($userData['email'], $array['subject'], $array['message']);

		$this->db->update('tbl_users', array('email_code' => $VerifyLinkCode), array('id' => $id));
	}

	public function userCheck($id = 0) {
		if(!($id > 0))
			return APIerror('Invalid User Id');

		$userCheckCount = $this->db->select('tbl_users', array('id','isActive'), array('id' => $id))->result();
		if($userCheckCount)
		{
			if($userCheckCount['isActive'] == 'd')
				return APIerror('User Deactivated By Admin');
		}
		else
		{
			return APIerror('User Not Found');
		}
	}

	public function userBlockCheck($sender_id = 0, $receiver_id = 0, $type = 'c') {
		if(!($sender_id > 0 && $receiver_id > 0))
			return APIerror('Invalid User Id');

		if($sender_id == $receiver_id)
			return;

		$sender_id = (int)$sender_id;
		$receiver_id = (int)$receiver_id;
		$userBlockCheckDB = $this->db->pdoQuery("SELECT sender_id,receiver_id FROM tbl_user_block
													WHERE $sender_id IN (sender_id,receiver_id)
													AND $receiver_id IN (sender_id,receiver_id) ")->results();
		if(count($userBlockCheckDB) > 0)
		{
			if($type == 'c')
				return APIerror('User Blocked');
			else
				return APIerror('User Already Blocked');
		}
	}

	public function userFollowCheck($sender_id = 0, $receiver_id = 0) {
		if(!($sender_id > 0 && $receiver_id > 0))
			return APIerror('Invalid User Id');

		$userFollowCheckDB = $this->db->count('tbl_user_follow', array('sender_id' => $sender_id, 'receiver_id' => $receiver_id));
		if($userFollowCheckDB > 0)
		{
			return 'y';
		}
		else
		{
			return 'n';
		}
	}

	public function userFriendCheck($sender_id = 0, $receiver_id = 0) {
		if(!($sender_id > 0 && $receiver_id > 0))
			return APIerror('Invalid User Id');

		$sender_id = (int)$sender_id;
		$receiver_id = (int)$receiver_id;
		$request_check_DB = $this->db->pdoQuery("SELECT sender_id,receiver_id,status FROM tbl_user_friend
													WHERE $sender_id IN (sender_id,receiver_id)
													AND $receiver_id IN (sender_id,receiver_id)")->result();
		if($request_check_DB)
		{
			if($request_check_DB['status'] == 'Friends')
				return 'y';
			else if($request_check_DB['sender_id'] == $sender_id && $request_check_DB['receiver_id'] == $receiver_id)
				return 's';
			else if($request_check_DB['sender_id'] == $receiver_id && $request_check_DB['receiver_id'] == $sender_id)
				return 'r';
			else 
				return '';
		}
		else
		{
			return 'n';
		}
	}

	public function postCheck($id = 0) {
		if(!($id > 0))
			return APIerror('Invalid Post Id');

		$postCheckCount = $this->db->count('tbl_user_post', array('id' => $id));
		if($postCheckCount == 0)
		{
			return APIerror('Post Not Found');
		}
	}

	public function commentCheck($id = 0) {
		if(!($id > 0))
			return APIerror('Invalid Comment Id');

		$commentCheckCount = $this->db->count('tbl_user_comment', array('id' => $id));
		if($commentCheckCount == 0)
		{
			return APIerror('Comment Not Found');
		}
	}

	public function subcommentCheck($id = 0) {
		if(!($id > 0))
			return APIerror('Invalid Reply Id');

		$subcommentCheckCount = $this->db->count('tbl_user_subcomment', array('id' => $id));
		if($subcommentCheckCount == 0)
		{
			return APIerror('Reply Not Found');
		}
	}

	public function pageCheck($id = 0) {
		if(!($id > 0))
			return APIerror('Invalid Page No.');
	}

	public function userAddNotification($sender_id = 0, $receiver_id = 0, $post_id =0 ,$noti_type = '') {
		if($noti_type != 'user-message' && $noti_type != 'post-reported')
			$this->db->insert('tbl_user_notification', array('sender_id' => $sender_id, 'receiver_id' => $receiver_id, 'post_id'=>$post_id, 'noti_type' => $noti_type));
		
		$userData = $this->getUserData($sender_id);
		$UserTokenDB = $this->db->select('tbl_users', array('device','phone_token'), array('id' => $receiver_id))->result();
		$UserNotiSettingsDB = $this->db->select('tbl_user_notification_settings', '*', array('user_id' => $receiver_id))->result();

		if($UserTokenDB && $UserNotiSettingsDB &&  $UserTokenDB['phone_token'] != '' && $UserTokenDB['phone_token'] != '123')
		{
			if($UserTokenDB['device'] == 'Android')
			{	
				if($UserNotiSettingsDB['post_cmnt'] == 'y' && $noti_type == 'post-commented')
				{
					$this->userSendNotificationAndroid($UserTokenDB['phone_token'], $userData['first_name'].' Comment On Your Post');
				}
				else if($UserNotiSettingsDB['post_like'] == 'y' && $noti_type == 'post-liked')
				{
					$this->userSendNotificationAndroid($UserTokenDB['phone_token'], $userData['first_name'].' Liked Your Post');
				}
				else if($UserNotiSettingsDB['req_rece'] == 'y' && $noti_type == 'request-received')
				{
					$this->userSendNotificationAndroid($UserTokenDB['phone_token'], $userData['first_name'].' Sent You Request');
				}
				else if($UserNotiSettingsDB['req_acpt'] == 'y' && $noti_type == 'request-accepted')
				{
					$this->userSendNotificationAndroid($UserTokenDB['phone_token'], $userData['first_name'].' Accepted Your Request');
				}
				else if($UserNotiSettingsDB['user_follow'] == 'y' && $noti_type == 'user-followed')
				{
					$this->userSendNotificationAndroid($UserTokenDB['phone_token'], $userData['first_name'].' Started Following You');
				}
				else if($UserNotiSettingsDB['user_msg'] == 'y' && $noti_type == 'user-message')
				{
					$this->userSendNotificationAndroid($UserTokenDB['phone_token'], $userData['first_name'].' Sent You Message');
				}
				else if($noti_type == 'post-reported')
				{
					$this->userSendNotificationAndroid($UserTokenDB['phone_token'], $userData['first_name'].' Reported Your Post');
				}
			}
			else if($UserTokenDB['device'] == 'iOS')
			{
				if($UserNotiSettingsDB['post_cmnt'] == 'y' && $noti_type == 'post-commented')
				{
					$this->userSendNotificationiOS($UserTokenDB['phone_token'], $userData['first_name'].' Comment On Your Post');
				}
				else if($UserNotiSettingsDB['post_like'] == 'y' && $noti_type == 'post-liked')
				{
					$this->userSendNotificationiOS($UserTokenDB['phone_token'], $userData['first_name'].' Liked Your Post');
				}
				else if($UserNotiSettingsDB['req_rece'] == 'y' && $noti_type == 'request-received')
				{
					$this->userSendNotificationiOS($UserTokenDB['phone_token'], $userData['first_name'].' Sent You Request');
				}
				else if($UserNotiSettingsDB['req_acpt'] == 'y' && $noti_type == 'request-accepted')
				{
					$this->userSendNotificationiOS($UserTokenDB['phone_token'], $userData['first_name'].' Accepted Your Request');
				}
				else if($UserNotiSettingsDB['user_follow'] == 'y' && $noti_type == 'user-followed')
				{
					$this->userSendNotificationiOS($UserTokenDB['phone_token'], $userData['first_name'].' Started Following You');
				}
				else if($UserNotiSettingsDB['user_msg'] == 'y' && $noti_type == 'user-message')
				{
					$this->userSendNotificationiOS($UserTokenDB['phone_token'], $userData['first_name'].' Sent You Message');
				}
				else if($noti_type == 'post-reported')
				{
					$this->userSendNotificationiOS($UserTokenDB['phone_token'], $userData['first_name'].' Reported Your Post');
				}
			}
		}
	}

	public function userSendNotificationAndroid($sender_token = '', $noti_message = '') {
		$msg = array('body' => $noti_message,
					'title'	=> SITE_NM,
					// 'style' => 'inbox',
					// 'image'	=> 'https://images.google.com/images/branding/googleg/1x/googleg_standard_color_128dp.png',
					// 'picture'	=> 'https://images.google.com/images/branding/googleg/1x/googleg_standard_color_128dp.png',
					'icon'	=> 'myicon',/*Default Icon*/
					'sound' => 'mySound'/*Default sound*/
	          );
		$fields = array('to' => $sender_token, 'notification' => $msg);
		
		$headers = array('Authorization: key=' . API_ACCESS_KEY, 'Content-Type: application/json');
		#Send Reponse To FireBase Server	
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
		$result = curl_exec($ch );
		curl_close( $ch );
	}

	public function userSendNotificationiOS($sender_token = '', $noti_message = '') {
		
		$deviceToken = $sender_token;
		$ctx = stream_context_create();
		// ck.pem is your certificate file
		stream_context_set_option($ctx, 'ssl', 'local_cert', 'apns-dev-cert.pem');
		stream_context_set_option($ctx, 'ssl', 'passphrase', '');
		// Open a connection to the APNS server
		$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err,$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		if (!$fp)
		{
			return;
			// exit("Failed to connect: $err $errstr" . PHP_EOL);
		}
		// Create the payload body
		$body['aps'] = array(
			'alert' => array(
			    'title' => SITE_NM,
                'body' => $noti_message,
			 ),
			'sound' => 'default'
		);
		// Encode the payload as JSON
		$payload = json_encode($body);
		// Build the binary notification
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
		// Send it to the server
		$result = fwrite($fp, $msg, strlen($msg));
		
		// Close the connection to the server
		fclose($fp);
		/*if (!$result)
			return 'Message not delivered' . PHP_EOL;
		else
			return 'Message successfully delivered' . PHP_EOL;*/
	}

	public function updateLocation($data = array()) {

		if(empty($data))
		APIerror('Invalid Data');

		extract($data);

		$res = $this->db->update('tbl_users', array('city_lat' => $lat, 'city_long' => $lng), array('id' => $user_id));

		return APIsuccess('Location Updated');

	}

	public function updateFCM($data = array()) {

		if(empty($data))
		APIerror('Invalid Data');

		extract($data);

		$res = $this->db->update('tbl_users', array('FCM_TOKEN' => $token), array('id' => $user_id));

		return APIsuccess('FCM Token Updated');

	}

	public function getPost($data = array()) {

		if(empty($data))
		APIerror('Invalid Data');

		extract($data);

		$userPostArrayDB = $this->db->pdoQuery("SELECT tbp.*,tbu.first_name,tbu.last_name,tbu.profile_img,tbu.cover_img
			FROM tbl_user_post AS tbp
			LEFT JOIN tbl_users AS tbu
			ON tbp.user_id = tbu.id
			WHERE tbp.id = $post_id LIMIT 1")->results();

		$response = array();
	
		$response["post"]  = $userPostArrayDB[0];
		$response["post"]["total_like"]  = $this->countLike($post_id);
		$response["post"]["total_comment"]  = $this->countComment($post_id);
		$response["post"]["diff"] = $this->getTimeDiff($userPostArrayDB[0]['createdDate']);
		$response['userData'] = $this->getUserData(56);
		$datetime = new DateTime($userPostArrayDB[0]['createdDate']);
		$response['post']['post_date'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Date");
		$response['post']['post_time'] = DateFormat($datetime->format('Y-m-d H:i:s'), "Time");
		$response['post']['is_liked'] = $this->isLikedCheck($user_id, $post_id);

		$userPostImageArrayDB = $this->db->select('tbl_user_post_image', array('id','image'), array('post_id' => $post_id))->results();

		$fetchimage = $userPostImageArrayDB[0];
		if(isset($fetchimage['image']))
		$response['post']['image'] = SITE_UPD.'post-nct/'.$user_id.'/'.$fetchimage['image'];
		else 
		$response['post']['image'] = null;	

		return APIsuccess(SUC,$response);
	}

	public function setAvailability($data = array()) {
		if(empty($data))
		APIerror('Invalid Data');

		extract($data);

		$res = $this->db->update('tbl_users', array('isOnline' => $isOnline), array('id' => $user_id));

		return APIsuccess('Availability Updated');
	}


	public function sendNotification($data = array()) {

		if(empty($data))
		APIerror('Invalid Data');

		extract($data);

		if($notification_action == "post_like") {

			$this->msg = $user_name." has liked your post";

		} else if($notification_action == "post_cmnt") {

			$this->msg = $user_name." has commented on your post";

		} else if($notification_action == "req_rece") {

			$this->msg = $user_name." has sent you a friend request";

		} else if($notification_action == "req_acpt") {

			$this->msg = $user_name." has accepted your friend request";

		} else if($notification_action == "user_follow") {

			$this->msg = $user_name." has followed you";

		} else if($notification_action == "user_msg") {

			$this->msg = $user_name." has messaged you";
		}

		$this->sendMessage($this->msg,$friend_token,$notification_action, $post_id, $friend_id);

	}

	public function sendMessage($message, $device_id, $not_type, $post_id =0, $friend_id){

			$content = array(
			"en" => $message
			);

			$include_player_id = array(
	        	$device_id
	      	);

			$fields = array(
			'app_id' => "ee5169a7-d089-4de3-98c4-4c6bc8378925",
			'include_player_ids' => array($device_id),
			'contents' => $content,
			'isIos' => true, 
			'ios_badgeType' => "Increase", 
			'ios_badgeCount' => 1,
			'data'=> array("notification_type"=> $not_type, "post_id" => $post_id, "user_id" => $friend_id)
			);

			$fields = json_encode($fields);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
			                                       'Authorization: Basic ZDM2ZjcyNzQtYjA1MS00YTE5LWI3YWUtNTRmZmVhZTA0NjY0'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);    

			$response = curl_exec($ch);
			curl_close($ch);

			$userData = $this->getUserData('154');

			return APIsuccess(SUC, json_decode($response));
	}

	public function getTimeDiff($date) {

		$datetime1 = new DateTime($date);
		$datetime2 = new DateTime(date('Y-m-d H:i:s'));

		$interval = $datetime1->diff($datetime2);
		$diff = date_diff($datetime1, $datetime2);

		$time_diff;

		if($diff->y != 0)
		{
			$time_diff = $diff->y. " yr";
		}
		else if($diff->m != 0)
		{
			$time_diff = $diff->m. " month";
		}
		else if($diff->d != 0)
		{
			$time_diff = $diff->d * (24). " h";
		}
		else if($diff->h != 0)
		{
			$time_diff = $diff->h. " h";
		}
		else if($diff->i != 0)
		{
			$time_diff = $diff->i. " m";
		}
		else if($diff->i == 0)
		{
			$time_diff = " Just now";
		}

		return $time_diff;
	}
	
}

?>
