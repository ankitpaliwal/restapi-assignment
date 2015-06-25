<?php

class UserController
{
    /**
     * Constructor: __construct
     * Create new object for user model
     */
    public function __construct($conn){
        $this->user = new UserModel($conn);
    }

    /**
     * Method : getUser
     * Arguments : Request object created by MyAPI
     * Handles get request to api/v1/users or api/v1/users/:id
     * Returns list of all users if api/v1/users is called
     * Returns details for a single user if api/v1/users/:id
     */
    public function getUser($request) {

        $args = $request->args;

        if (count($args) > 1) {
            $responseJson = $this->createResponseArray(404,array("msg"=>"Page Not Found"));
            return $responseJson;
        }
        
        if (isset($args[0])) {

            $user_id = (int)$args[0];

            $result = $this->user->getUserById($user_id);

            if (empty($result)) {
                $responseJson = $this->createResponseArray(404,array("msg"=>"Page Not Found"));
                return $responseJson;
            }
            else{
                $return_message = array("msg"=>"Got details of single user", "data"=>$result);
            }            

        }
        else{
            $result = $this->user->getAllUsers();

            if (empty($result)) {
                $return_message = array("msg"=>"No users added to database", "data"=>array());
            }
            else{
                $return_message = array("msg"=>"Got list of all users", "data"=>$result);
            }
            
        }

        $responseJson = $this->createResponseArray(200,$return_message);
        return $responseJson;

    }
    

     /**
     * Method : postUser
     * Arguments : Request object created by MyAPI
     * Handles post request to api/v1/users
     * Expects multipart or encoded data to create a new users
     * Creates a new user in the database
     */
    public function postUser($request) {

        $args = $request->args;

        $input_data = $request->request;

        if (count($args) != 0) {
            $responseJson = $this->createResponseArray(404,array("msg"=>"Page Not Found"));
            return $responseJson;
        }

        if (empty($input_data)) {
            $responseJson = $this->createResponseArray(400, array("msg"=>"Did not get any input"));
            return $responseJson;
        }

        $result = $this->user->addNewUser($input_data);

        if (isset($result) && !empty($result) && 1 == $result['status']) {
            $responseJson = $this->createResponseArray(200, array("msg"=>"New User Created","data" => array("id"=>$result['id'])));
        }
        else{
            $responseJson = $this->createResponseArray(500, array("msg"=>"Something went wrong while creating a new user. Please try again. Error - ".$result['msg']));
        }

        return $responseJson;
    }

    /**
     * Method : putUser
     * Arguments : Request object created by MyAPI
     * Handles put request to api/v1/users/:id
     * Expects encoded data to update user details
     * Updates details for user :id
     */
    public function putUser($request){

        $args = $request->args;

        if (count($args) != 1) {
            $responseJson = $this->createResponseArray(404,array("msg"=>"Page Not Found"));
            return $responseJson;
        }

        $input_file = $request->file;

        if(isset($_SERVER['CONTENT_TYPE'])) {
            $content_type = $_SERVER['CONTENT_TYPE'];

            if (strpos($content_type, 'multipart') !== false) {
                $responseJson = $this->createResponseArray(400,array("msg"=>"Sorry, put request does not support multipart form data. Please send the data in encoded format"));
                return $responseJson;
            }
        }

        $input_data = array();

        parse_str($input_file, $postvars);
        foreach($postvars as $field => $value) {
            $input_data[$field] = $value;
        }

        if (empty($input_data)) {
            $responseJson = $this->createResponseArray(400, array("msg"=>"Did not get any input"));
            return $responseJson;
        }

        $user_id = (int)$args[0];

        $result = $this->user->updateUser($user_id,$input_data);

        if (1 == $result['status']) {
            $responseJson = $this->createResponseArray(200,array("msg" => "User details updated","id" => $user_id));
        }
        else{
            $responseJson = $this->createResponseArray(500,array("msg"=>"Something went wrong while updating user details. Please try again. Error - ".$result['msg']));
        }

        return $responseJson;

    }

     /**
     * Method : deleteUser
     * Arguments : Request object created by MyAPI
     * Handles delete request to api/v1/users/:id
     * Removed user :id and details for that user from the database
     */
    public function deleteUser($request){

        $args = $request->args;

        if (count($args) != 1) {
            $responseJson = $this->createResponseArray(404,array("msg"=>"Page Not Found"));
            return $responseJson;
        }          

        $user_id = (int)$args[0];

        $result = $this->user->deleteUser($user_id);

        if (1 == $result['status']) {
            $responseJson = $this->createResponseArray(200,array("msg"=>"User Removed"));
        }
        else{
            $responseJson = $this->createResponseArray(500,array("msg"=>"Something went wrong while removing the user. Please try again. Error - ".$result['msg']));
        }

        return $responseJson;
    }

    /**
     * Method : createResponseArray
     * Arguments : Status Code and Data
     * Created payload to be returned
     */
    private function createResponseArray($status_code,$data){
        return array("status" => $status_code, "data" => $data);
    }
}