<?php

$server_key = "SB-Mid-server-U4Rl-v_VLt-SlGQ1tL9vl4E_";
$is_production = false;
$api_url = $is_production ? 
  'https://app.midtrans.com/snap/v1/transactions' : 
  'https://app.sandbox.midtrans.com/snap/v1/transactions';

  
class Home extends CI_Controller {

	public function index()
	{
		$this->load->view('home');
	}
	public function payment()
	{
		$this->load->view('pay');
	}
	public function notif()
	{	$urldb = "https://kasver-92d84-default-rtdb.asia-southeast1.firebasedatabase.app";
		$params = array('server_key' => 'SB-Mid-server-U4Rl-v_VLt-SlGQ1tL9vl4E_', 'production' => false);
		$this->load->library('veritrans');
		$this->veritrans->config($params);
		$this->load->helper('url');
		
		echo 'test notification handler';
		$json_result = file_get_contents('php://input');
		$result = json_decode($json_result);
		
		if($result){
		$notif = $this->veritrans->status($result->order_id);

		}

		//notification handler sample

		
		$transaction = $notif->transaction_status;
		$type = $notif->payment_type;
		$order_id = $notif->order_id;
		$fraud = $notif->fraud_status;
		$transaction_id = $notif->transaction_id;
		

		if ($transaction == 'capture') {
		  // For credit card transaction, we need to check whether transaction is challenge by FDS or not
		  if ($type == 'credit_card'){
		    if($fraud == 'challenge'){
		      // TODO set payment status in merchant's database to 'Challenge by FDS'
		      // TODO merchant should decide whether this transaction is authorized or not in MAP
		      echo "Transaction order_id: " . $order_id ." is challenged by FDS";
		      } 
		      else {
		      // TODO set payment status in merchant's database to 'Success'
		      echo "Transaction order_id: " . $order_id ." successfully captured using " . $type;
		      }
		    }
		  }
		else if ($transaction == 'settlement'){
		  // TODO set payment status in merchant's database to 'Settlement'
		  $db = new firebaseRDB($urldb);
		  $update = $db->update("Payment", $order_id, [
			"status"     => $transaction,
			"claim" => "1"
		 ]);
		  } 
		  else if($transaction == 'pending'){
		  // TODO set payment status in merchant's database to 'Pending'
		
		  } 
		  else if ($transaction == 'deny') {
		  // TODO set payment status in merchant's database to 'Denied'
		  
		}
		else if ($transaction == 'expire') {
			// TODO set payment status in merchant's database to 'Denied'
			$db = new firebaseRDB($urldb);
		  $update = $db->update("Payment", $order_id, [
			"status"     => $transaction,
			"claim" => "0"
		 ]);
		  }

	}
 }

 class firebaseRDB{
	function __construct($url=null) {
		
	   if(isset($url)){
		  $this->url = $url;
	   }else{
		  throw new Exception("Database URL must be specified");
	   }
	}
	
	public function grab($url, $method, $par=null){
	   $ch = curl_init();
	   curl_setopt($ch, CURLOPT_URL, $url);
	   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	   if(isset($par)){
		  curl_setopt($ch, CURLOPT_POSTFIELDS, $par);
	   }
	   curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	   curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	   curl_setopt($ch, CURLOPT_HEADER, 0);
	   $html = curl_exec($ch);
	   $json = json_decode(utf8_encode($html), true);
	  
	  
	}
 
 
	public function insert($table, $result){
	   $path = $this->url."/$table.json";
	   $grab = $this->grab($path, "POST", json_encode($result));
	   
	   return $grab;
	}
 
	public function update($table, $uniqueID, $data){
		$path = $this->url."/$table/$uniqueID.json";
		$grab = $this->grab($path, "PATCH", json_encode($data));
		return $grab;
	 }
	 
	public function delete($table, $uniqueID){
	   $path = $this->url."/$table/$uniqueID.json";
	   $grab = $this->grab($path, "DELETE");
	   return $grab;
	}
 
	public function retrieve($dbPath, $queryKey=null, $queryType=null, $queryVal =null){
		if(isset($queryType) && isset($queryKey) && isset($queryVal)){
		   $queryVal = urlencode($queryVal);
		   if($queryType == "EQUAL"){
				 $pars = "orderBy=\"$queryKey\"&equalTo=\"$queryVal\"";
		   }elseif($queryType == "LIKE"){
				 $pars = "orderBy=\"$queryKey\"&startAt=\"$queryVal\"";
		   }
		}
		$pars = isset($pars) ? "?$pars" : "";
		$path = $this->url."/$dbPath.json$pars";
		$grab = $this->grab($path, "GET");
		return $grab;
	 }

 
 }
