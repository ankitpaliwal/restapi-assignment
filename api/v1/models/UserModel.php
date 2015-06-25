<?php

class UserModel {

	public function __construct($conn){
		$this->conn = $conn;
	}

	/**
	 * Fetch details of user by id
	 * @param  int $user_id
	 * @return array Details of user
	 */
	function getUserById($user_id){

		$user_details = array();
		$stmt = '';

		$stmt = $this->conn->prepare("SELECT u.id,u.username,u.email,ud.mobile_no,ud.address,ud.city,ud.state,ud.pincode FROM users u INNER JOIN user_details ud on u.id = ud.user_id WHERE u.id = :id");
		$stmt->bindParam(':id',$user_id);
		$stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

		$user_details = $stmt->fetch();

		return $user_details;
	}

	/**
	 * Return all users and their details from database
	 * @return array array of users
	 */
	function getAllUsers(){

		$users = array();
		$sql = '';
		$stmt = '';
		$result = '';

		$sql = 'SELECT u.id,u.username,u.email,ud.mobile_no,ud.address,ud.city,ud.state,ud.pincode FROM users u INNER JOIN user_details ud on u.id = ud.user_id';

		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
        $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

		while ($r = $stmt->fetch()) {
			$users[$r['id']] = $r;
		}

		return $users;
	}

	/**
	 * Create a new user in database and store their details
	 * @param array $input
	 */
	function addNewUser($input){

		$inserted_user_id = '';
		$enc_pass = '';
		$user_stmt = '';
		$user_details_stmt = '';
		$curr_timestamp = '';

		$this->conn->beginTransaction();

		try{

			$enc_pass = md5($input['password']);
			$curr_timestamp = date("Y-m-d h:i:s");

			$user_stmt = $this->conn->prepare("INSERT INTO users (user_type,username,password,email,created_at) VALUES (:user_type,:username,:password,:email,:created_at)");
			$user_stmt->bindParam(':user_type',$input['user_type']);
			$user_stmt->bindParam(':username',$input['username']);
			$user_stmt->bindParam(':password',$enc_pass);
			$user_stmt->bindParam(':email',$input['email']);
			$user_stmt->bindParam(':created_at',$curr_timestamp);
	        $user_stmt->execute();

	        $inserted_user_id = $this->conn->lastInsertId();

	        $user_details_stmt = $this->conn->prepare("INSERT INTO user_details (user_id,mobile_no,address,city,state,pincode,created_at) VALUES (:user_id,:mobile_no,:address,:city,:state,:pincode,:created_at)");
	        $user_details_stmt->bindParam(':user_id',$inserted_user_id);
	        $user_details_stmt->bindParam(':mobile_no',$input['mobile_no']);
	        $user_details_stmt->bindParam(':address',$input['address']);
	        $user_details_stmt->bindParam(':city',$input['city']);
	        $user_details_stmt->bindParam(':state',$input['state']);
	        $user_details_stmt->bindParam(':pincode',$input['pincode']);
			$user_details_stmt->bindParam(':created_at',$curr_timestamp);
	        $user_details_stmt->execute();

	        $this->conn->commit();
	    }
	    catch(Exception $e){
	    	$this->conn->rollback();
	    	return array('status'=>0,'msg' => $e->getMessage());
	    }
	    return array('status' => 1,'id' => $inserted_user_id);
	}

	function updateUser($user_id,$input){

		$user_fields = array();
		$user_details_fields = array();
		$input_fields = array();
		$user_fields_to_update = array();
		$user_details_fields_to_update = array();
		$user_sql = '';
		$user_details_sql = '';
		$user_stmt = '';
		$user_details_stmt = '';

		$this->conn->beginTransaction();

		try{

			$user_fields = array('user_type','username','password','email','last_login');
			$user_details_fields = array('mobile_no','address','city','state','pincode');

			$input_fields = array_keys($input);

			$user_fields_to_update = array_intersect($user_fields, $input_fields);
			$user_details_fields_to_update = array_intersect($user_details_fields, $input_fields);

			if ( count( $user_fields_to_update ) > 0 ) {

				$user_sql = "UPDATE users SET ";

				foreach ($user_fields_to_update as $value) {
					$user_sql .= $value." = :$value, ";
				}
				$user_sql = substr($user_sql, 0, -2);
				$user_sql .= " WHERE id = :id";

				$user_stmt = $this->conn->prepare($user_sql);
				$user_stmt->bindParam(':id',$user_id);
				foreach ($user_fields_to_update as $value) {
					if ('password' == $value) {
						$enc_pass = md5($input['password']);
						$user_stmt->bindParam(':'.$value,$enc_pass);
					}
					else{
						$user_stmt->bindParam(':'.$value,$input[$value]);
					}
				}
		        $user_stmt->execute();
			}

			if ( count( $user_details_fields_to_update ) > 0 ) {
				$user_details_sql = "UPDATE user_details SET ";

				foreach ($user_details_fields_to_update as $value) {
					$user_details_sql .= $value." = :$value, ";
				}
				$user_details_sql = substr($user_details_sql, 0, -2);
				$user_details_sql .= " WHERE id = :id";

				$user_details_stmt = $this->conn->prepare($user_details_sql);
				foreach ($user_details_fields_to_update as $value) {
					$user_details_stmt->bindParam(':'.$value,$input[$value]);
				}
				$user_details_stmt->bindParam(':id',$user_id);
		        $user_details_stmt->execute();	
			}


	        $this->conn->commit();

		}
		catch(Exception $e){
			$this->conn->rollback();
	    	return array('status'=>0,'msg' => $e->getMessage());
		}
		return array('status' => 1);
	}

	/**
	 * Deleted the user by id
	 * @param  int $user_id
	 */
	function deleteUser($user_id){

		$user_stmt = '';
		$user_details_stmt = '';

		$this->conn->beginTransaction();

		try{
			$user_stmt = $this->conn->prepare("DELETE FROM users WHERE id = :id");
			$user_stmt->bindParam(':id',$user_id);
	        $user_stmt->execute();

	        $user_details_stmt = $this->conn->prepare("DELETE FROM user_details WHERE user_id = :id");
			$user_details_stmt->bindParam(':id',$user_id);
	        $user_details_stmt->execute();

	        $this->conn->commit();
		}
		catch(Exception $e){
			$this->conn->rollback();
			return array('status'=>0,'msg' => $e->getMessage());
		}
		return array('status' => 1);
	}
}

?>