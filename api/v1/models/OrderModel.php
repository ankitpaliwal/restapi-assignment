<?php

class OrderModel {

	public function __construct($conn){
		$this->conn = $conn;
	}

	/**
	 * Fetch details of order by id
	 * @param  int $order_id
	 * @return array Details of order
	 */
	function getOrderById($order_id){

		$order_details = array();
		$order_item_details = array();
		$order = array();
		$order_stmt = '';
		$order_items_stmt = '';
		$total_amount = 0;

		$order_stmt = $this->conn->prepare("SELECT user_id,delivery_address,address_coordinates,status FROM orders WHERE id = :id");
		$order_stmt->bindParam(':id',$order_id);
		$order_stmt->execute();
        $order_stmt->setFetchMode(PDO::FETCH_ASSOC);

		$order = $order_stmt->fetch();
		if (empty($order)) {
			return $order;
		}

		$order_items_stmt = $this->conn->prepare("SELECT oi.id,oi.item_id,oi.quantity,oi.amount,mi.item_name,mi.value FROM order_items oi INNER JOIN menu_items mi ON oi.item_id = mi.id WHERE order_id = :id");
		$order_items_stmt->bindParam(':id',$order_id);
		$order_items_stmt->execute();
        $order_items_stmt->setFetchMode(PDO::FETCH_ASSOC);

		$order_item_details = $order_items_stmt->fetchAll();

		$order_details = array(
				'user_id' => $order['user_id'],
				'delivery_address' => $order['delivery_address'],
				'address_coordinates' => $order['address_coordinates'],
				'status' => $order['status'],
				'items' => array()
			);

		foreach ($order_item_details as $order_item) {
			$order_details['items'][] = array(
					'order_item_id' => $order_item['id'],
					'item_id' => $order_item['item_id'],
					'item_name' => $order_item['item_name'],
					'item_value' => $order_item['value'],
					'quantity' => $order_item['quantity'],
					'amount' => $order_item['amount']
				);
			$total_amount += $order_item['amount'];
		}

		$order_details['total_amount'] = $total_amount;

		return $order_details;
	}

	/**
	 * Return all orders and their details from database
	 * @return array array of orders
	 */
	function getAllOrders(){

		$all_orders = array();
		$order_stmt = '';
		$order_items_stmt = '';
		$orders = array();

		$order_stmt = $this->conn->prepare("SELECT id,user_id,delivery_address,address_coordinates,status FROM orders");
		$order_stmt->execute();
        $order_stmt->setFetchMode(PDO::FETCH_ASSOC);

		$orders = $order_stmt->fetchAll();

		foreach ($orders as $key => $order) {

			$order_details = array();
			$order_item_details = array();
			$total_amount = 0;

			$order_items_stmt = $this->conn->prepare("SELECT oi.id,oi.item_id,oi.quantity,oi.amount,mi.item_name,mi.value FROM order_items oi INNER JOIN menu_items mi ON oi.item_id = mi.id WHERE order_id = :id");
			$order_items_stmt->bindParam(':id',$order['id']);
			$order_items_stmt->execute();
	        $order_items_stmt->setFetchMode(PDO::FETCH_ASSOC);

			$order_item_details = $order_items_stmt->fetchAll();

			$order_details = array(
					'id' => $order['id'],
					'user_id' => $order['user_id'],
					'delivery_address' => $order['delivery_address'],
					'address_coordinates' => $order['address_coordinates'],
					'status' => $order['status'],
					'items' => array()
				);

			foreach ($order_item_details as $order_item) {
				$order_details['items'][] = array(
						'order_item_id' => $order_item['id'],
						'item_id' => $order_item['item_id'],
						'item_name' => $order_item['item_name'],
						'item_value' => $order_item['value'],
						'quantity' => $order_item['quantity'],
						'amount' => $order_item['amount']
					);
				$total_amount += $order_item['amount'];
			}

			$order_details['total_amount'] = $total_amount;

			$all_orders[] = $order_details;
		}

		return $all_orders;
	}

	/**
	 * Create a new order in database and store their details
	 * @param array $input
	 */
	function addNewOrder($input){

		$inserted_order_id = '';
		$order_stmt = '';
		$order_details_stmt = '';
		$curr_timestamp = '';
		$item_amount = '';
		$per_item_amount = '';

		$this->conn->beginTransaction();

		try{

			$curr_timestamp = date("Y-m-d h:i:s");

			$order_stmt = $this->conn->prepare("INSERT INTO orders (user_id,delivery_address,address_coordinates,created_at) VALUES (:user_id,:delivery_address,:address_coordinates,:created_at)");
			$order_stmt->bindParam(':user_id',$input['user_id']);
			$order_stmt->bindParam(':delivery_address',$input['delivery_address']);
			$order_stmt->bindParam(':address_coordinates',$input['address_coordinates']);
			$order_stmt->bindParam(':created_at',$curr_timestamp);
	        $order_stmt->execute();

	        $inserted_order_id = $this->conn->lastInsertId();

	        foreach ($input['item_id'] as $key => $value) {

	        	$per_item_amount = $this->getItemPrice($input['item_id'][$key]);
	        	$item_amount = $per_item_amount*$input['quantity'][$key];

	        	$order_details_stmt = $this->conn->prepare("INSERT INTO order_items (order_id,item_id,quantity,amount,created_at) VALUES (:order_id,:item_id,:quantity,:amount,:created_at)");
		        $order_details_stmt->bindParam(':order_id',$inserted_order_id);
		        $order_details_stmt->bindParam(':item_id',$input['item_id'][$key]);
		        $order_details_stmt->bindParam(':quantity',$input['quantity'][$key]);
		        $order_details_stmt->bindParam(':amount',$item_amount);
				$order_details_stmt->bindParam(':created_at',$curr_timestamp);
		        $order_details_stmt->execute();
	        }

	        $this->conn->commit();
	    }
	    catch(Exception $e){
	    	$this->conn->rollback();
	    	return array('status'=>0,'msg' => $e->getMessage());
	    }
	    return array('status' => 1,'id' => $inserted_order_id);
	}

	/**
	 * Fetch price of item from database
	 * @param  [int] $item_id
	 * @return [int] Price of item
	 */
	function getItemPrice($item_id){

		$item_price = '';
		$stmt = '';

		$stmt = $this->conn->prepare("SELECT value FROM menu_items WHERE id = :id");
		$stmt->bindParam(':id',$item_id);
		$stmt->execute();

		$item_price = $stmt->fetchColumn();

		return $item_price;
	}


	/**
	 * Update Order Details
	 * @param  [int] $order_id
	 * @param  [array] $input 
	 */
	function updateOrder($order_id,$input){

		$order_fields = array();
		$input_fields = array();
		$order_fields_to_update = array();
		$order_sql = '';
		$order_stmt = '';
		$order_items_stmt = '';

		$this->conn->beginTransaction();

		try{

			$order_fields = array('delivery_address','address_coordinates','status');

			$input_fields = array_keys($input);

			$order_fields_to_update = array_intersect($order_fields, $input_fields);

			if ( count( $order_fields_to_update ) > 0 ) {

				$order_sql = "UPDATE orders SET ";

				foreach ($order_fields_to_update as $value) {
					$order_sql .= $value." = :$value ";
				}
				$order_sql .= "WHERE id = :id";

				$order_stmt = $this->conn->prepare($order_sql);
				$order_stmt->bindParam(':id',$order_id);
				foreach ($order_fields_to_update as $value) {
					$order_stmt->bindParam(':'.$value,$input[$value]);
				}
		        $order_stmt->execute();
			}


			if ( isset($input['order_item_action']) && count($input['order_item_action']) > 0 ) {
				foreach ($input['order_item_action'] as $key => $value) {

					$per_item_amount = '';
					$item_amount = '';
					$curr_timestamp = date("Y-m-d h:i:s");

					if ('update' == $value) {
						$per_item_amount = $this->getItemPrice($input['item_id'][$key]);
			        	$item_amount = $per_item_amount*$input['item_quantity'][$key];

						$order_items_stmt = $this->conn->prepare("UPDATE order_items SET quantity = :quantity, amount = :amount WHERE order_id = :order_id AND item_id = :item_id");
						$order_items_stmt->bindParam(':quantity',$input['item_quantity'][$key]);
						$order_items_stmt->bindParam(':amount',$item_amount);
						$order_items_stmt->bindParam(':order_id',$order_id);
						$order_items_stmt->bindParam(':item_id',$input['item_id'][$key]);
						$order_items_stmt->execute();	
					}
					else if('remove' == $value){
						$order_items_stmt = $this->conn->prepare("DELETE FROM order_items WHERE order_id = :order_id AND item_id = :item_id");
						$order_items_stmt->bindParam(':order_id',$order_id);
						$order_items_stmt->bindParam(':item_id',$input['item_id'][$key]);
						$order_items_stmt->execute();	
					}
					else if('add' == $value){
						$per_item_amount = $this->getItemPrice($input['item_id'][$key]);
			        	$item_amount = $per_item_amount*$input['item_quantity'][$key];

			        	$order_items_stmt = $this->conn->prepare("INSERT INTO order_items (order_id,item_id,quantity,amount,created_at) VALUES (:order_id,:item_id,:quantity,:amount,:created_at)");
				        $order_items_stmt->bindParam(':order_id',$order_id);
				        $order_items_stmt->bindParam(':item_id',$input['item_id'][$key]);
				        $order_items_stmt->bindParam(':quantity',$input['item_quantity'][$key]);
				        $order_items_stmt->bindParam(':amount',$item_amount);
						$order_items_stmt->bindParam(':created_at',$curr_timestamp);
				        $order_items_stmt->execute();
					}
					else{
						return array('status'=>0,'msg' => 'Unknown Action');
					}
				}
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
	 * Deleted the order by id
	 * @param  int $order_id
	 */
	function deleteOrder($order_id){

		$order_stmt = '';

		try{
			$order_stmt = $this->conn->prepare("UPDATE orders SET status = 3 WHERE id = :id");
			$order_stmt->bindParam(':id',$order_id);
	        $order_stmt->execute();
		}
		catch(Exception $e){
			return array('status'=>0,'msg' => $e->getMessage());
		}
		return array('status' => 1);
	}
}

?>