<?php

class OrderController
{
    /**
     * Constructor: __construct
     * Create new object for order model
     */
    public function __construct($conn){
        $this->order = new OrderModel($conn);
    }

    /**
     * Method : getOrder
     * Arguments : Request object created by MyAPI
     * Handles get request to api/v1/orders or api/v1/orders/:id
     * Returns list of all orders if api/v1/orders is called
     * Returns details for a single order if api/v1/orders/:id
     */
    public function getOrder($request) {

        $args = $request->args;

        if (count($args) > 1) {
            $responseJson = $this->createResponseArray(404,array("msg"=>"Page Not Found"));
            return $responseJson;
        }
        
        if (isset($args[0])) {

            $order_id = (int)$args[0];

            $result = $this->order->getOrderById($order_id);

            if (empty($result)) {
                $responseJson = $this->createResponseArray(404,array("msg"=>"Page Not Found"));
                return $responseJson;
            }
            else{
                $return_message = array("msg"=>"Got details of single order", "data"=>$result);
            }            

        }
        else{
            $result = $this->order->getAllOrders();

            if (empty($result)) {
                $return_message = array("msg"=>"No orders added to database", "data"=>array());
            }
            else{
                $return_message = array("msg"=>"Got list of all orders", "data"=>$result);
            }
            
        }

        $responseJson = $this->createResponseArray(200,$return_message);
        return $responseJson;

    }
    

     /**
     * Method : postOrder
     * Arguments : Request object created by MyAPI
     * Handles post request to api/v1/orders
     * Expects multipart or encoded data to create a new orders
     * Creates a new order in the database
     */
    public function postOrder($request) {

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

        $result = $this->order->addNewOrder($input_data);

        if (isset($result) && !empty($result) && 1 == $result['status']) {
            $responseJson = $this->createResponseArray(200, array("msg"=>"New Order Created","data" => array("id"=>$result['id'])));
        }
        else{
            $responseJson = $this->createResponseArray(500, array("msg"=>"Something went wrong while creating a new order. Please try again. Error - ".$result['msg']));
        }

        return $responseJson;
    }

    /**
     * Method : putOrder
     * Arguments : Request object created by MyAPI
     * Handles put request to api/v1/orders/:id
     * Expects encoded data to update order details
     * Updates details for order :id
     */
    public function putOrder($request){

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

        $order_id = (int)$args[0];

        $result = $this->order->updateOrder($order_id,$input_data);

        if (1 == $result['status']) {
            $responseJson = $this->createResponseArray(200,array("msg" => "Order details updated","id" => $order_id));
        }
        else{
            $responseJson = $this->createResponseArray(500,array("msg"=>"Something went wrong while updating order details. Please try again. Error - ".$result['msg']));
        }

        return $responseJson;

    }

     /**
     * Method : deleteOrder
     * Arguments : Request object created by MyAPI
     * Handles delete request to api/v1/orders/:id
     * Removed order :id and details for that order from the database
     */
    public function deleteOrder($request){

        $args = $request->args;

        if (count($args) != 1) {
            $responseJson = $this->createResponseArray(404,array("msg"=>"Page Not Found"));
            return $responseJson;
        }          

        $order_id = (int)$args[0];

        $result = $this->order->deleteOrder($order_id);

        if (1 == $result['status']) {
            $responseJson = $this->createResponseArray(200,array("msg"=>"Order Removed"));
        }
        else{
            $responseJson = $this->createResponseArray(500,array("msg"=>"Something went wrong while removing the order. Please try again. Error - ".$result['msg']));
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