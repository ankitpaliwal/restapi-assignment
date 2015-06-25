<?php

/**
 * Include php files required
 */
require_once __DIR__ .'/inc/API.php';

/**
 * Add our controllers, methods and views to __autoload function when class_exists function is called
 */
spl_autoload_register('apiAutoload');
function apiAutoload($classname)
{
    if (preg_match('/[a-zA-Z]+Controller$/', $classname)) {
        include __DIR__ . '/controllers/' . $classname . '.php';
        return true;
    } elseif (preg_match('/[a-zA-Z]+Model$/', $classname)) {
        include __DIR__ . '/models/' . $classname . '.php';
        return true;
    } elseif (preg_match('/[a-zA-Z]+View$/', $classname)) {
        include __DIR__ . '/views/' . $classname . '.php';
        return true;
    }
}


/**
 * class : MyAPI
 * Extends API base class and contains methods for endpoints
 */
class MyAPI extends API
{
    
    /**
     * Property: User
     * Used to store authenticated users object
     */
    protected $User;

    /**
     * Constructor: __construct
     * Call parent construct and connect to database
     */
    public function __construct($request, $origin) {
        parent::__construct($request);

        try {
            $servername = "localhost";
            $username = "restapi-assignme";
            $password = "yXydwAJSR8VaWtZr";
            $myDB = "restapi-assignment";

            $this->conn = new PDO("mysql:host=$servername;dbname=$myDB", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e) {
            echo json_encode(Array('error' => $e->getMessage()));
        }
    }

    /**
     * User Endpoint
     * Called by processApi in API Class when url is api/v1/user/*
     */
     protected function user() {

        $controller_name = ucfirst($this->endpoint) . 'Controller';
        if (class_exists($controller_name)) {
            $controller = new $controller_name($this->conn);
            $action_name = strtolower($this->method) . ucfirst($this->endpoint);
            $result = $controller->$action_name($this);

            return $result;
        }
        else{
            return array("status" => 404, "data" => array("msg"=>"Page Not Found"));
        }
    }

    /**
     * Order Endpoint
     * Called by processApi in API Class when url is api/v1/order/*
     */
     protected function order() {

        $controller_name = ucfirst($this->endpoint) . 'Controller';
        if (class_exists($controller_name)) {
            $controller = new $controller_name($this->conn);
            $action_name = strtolower($this->method) . ucfirst($this->endpoint);
            $result = $controller->$action_name($this);

            return $result;
        }
        else{
            return array("status" => 404, "data" => array("msg"=>"Page Not Found"));
        }
    }
}

 // Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {
    $API = new MyAPI($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
    echo $API->processAPI();
} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}
?>