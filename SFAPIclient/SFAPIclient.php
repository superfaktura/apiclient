<?php
/**
 * @category   SuperFaktura API
 * @author     SuperFaktura.sk s.r.o. <info@superfaktura.sk>
 * @version    1.16
 * @lastUpdate 05.11.2018
 *
 */

if (!class_exists('Requests')) {
    require_once('Requests.php');
}


class SFAPIclient {

	protected
		$email,
		$apikey,
		$company_id,
		$headers,
		$className,
		$timeout = 30;

	public
		$data = array(
			'Invoice' => array(),
			'Expense' => array(),
			'Client' => array(),
			'InvoiceItem' => array(),
			'MyData' => array(),
		);

	const
		API_AUTH_KEYWORD = 'SFAPI',
		SFAPI_URL = 'https://moja.superfaktura.sk';


	public function __construct($email, $apikey, $apptitle = '', $module = 'API', $company_id = '') {
		Requests::register_autoloader();
	
		$this->className  = get_class($this);
		$this->email      = $email;
		$this->apikey     = $apikey;
		$this->company_id = $company_id;
		$this->headers    = array(
			'Authorization' => self::API_AUTH_KEYWORD." " . http_build_query(array('email' => $this->email, 'apikey' => $this->apikey, 'company_id' => $this->company_id))
		);
		$this->data['apptitle'] = $apptitle;
		$this->data['module'] = $module;

	}

	private function _setData($dataSet, $key, $value) {
		if (is_array($key)) {
			$this->data[$dataSet] = array_merge($this->data[$dataSet], $key);
			if (empty($key)) {
				$this->data[$dataSet] = array();
			}
		} else {
			$this->data[$dataSet][$key] = $value;
		}
	}

	private function _getRequestParams($params, $list_info = true) {
		$request_params = "";
		if ($list_info) {
			$request_params .= "/listinfo:1";
		}
		if (isset($params['search'])) {
			$params['search'] = base64_encode($params['search']);
		}elseif (isset($params['sku'])) {
			$params['sku'] = base64_encode($params['sku']);
		}
		
		foreach ($params as $k => $v) {
			$request_params .= "/$k:$v";
		}
		return $request_params;
	}

	protected function exceptionHandling($e) {
		$response_data = new stdClass();
		$response_data->error = 99;
		$response_data->error_message = $e->getMessage();

		return $response_data;

	}

	public function resetData($options = array()) {
		if (empty($options)) {
			$options = array('Invoice', 'InvoiceItem', 'Expense', 'Client');
		} 
		foreach ($options as $option) {
			$this->data[$option] = array();
		} 
	}

	public function addItem($item = array()) {
		$this->data['InvoiceItem'][] = $item;
	}

	public function deleteInvoiceItem($invoice_id, $item_id) {
		try{
			if (!is_array($item_id)) {
				$item_id = [$item_id];
			}
			$response = Requests::get($this->getConstant('SFAPI_URL').'/invoice_items/delete/'.implode(",", $item_id).'/invoice_id:'.$invoice_id, $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}
		catch (Exception $e) {		
			return $this->exceptionHandling($e); 
		}
	}

	public function deleteExpense($id) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/expenses/delete/'.$id, $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function addTags($tag_ids = array()) {
		$this->data['Tag']['Tag'] = $tag_ids;
	}

	public function clients($params = array(), $list_info = true) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/clients/index.json'.$this->_getRequestParams($params, $list_info), $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}		

	}

	public function delete($id) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/invoices/delete/'.$id, $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); ;
		}
	}

	public function deleteStockItem($id) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/stock_items/delete/'.$id, $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}	
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}	
	}

	public function expenses($params = array(), $list_info = true) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/expenses/index.json'.$this->_getRequestParams($params, $list_info), $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	protected function getConstant($const) {
		return constant(get_class($this)."::".$const);
	}

	public function getCountries() {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/countries', $this->headers, array('timeout' => $this->timeout));
			return json_decode($response->body);
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function getSequences() {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/sequences/index.json', $this->headers, array('timeout' => $this->timeout));
			return json_decode($response->body);
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function getTags() {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/tags/index.json', $this->headers, array('timeout' => $this->timeout));
			return json_decode($response->body);
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}	
	}

	public function getPDF($invoice_id, $token, $language = 'slo') {
		try{
			//mozne hodnoty language [eng,slo,cze]
			$response = Requests::get($this->getConstant('SFAPI_URL').'/'.$language.'/invoices/pdf/'.$invoice_id.'/token:'.$token, $this->headers, array('timeout' => $this->timeout));
			return $response->body;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function invoice($id) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/invoices/view/'.$id.'.json', $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function invoices($params = array(), $list_info = true) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/invoices/index.json'.$this->_getRequestParams($params, $list_info), $this->headers, array('timeout' => $this->timeout));			
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function expense($id) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/expenses/edit/'.$id.'.json', $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); ;
		}
	}

	public function stockItem($id) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/stock_items/edit/'.$id.'.json', $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function addStockMovement($options) {
		$data = array();
		$data['StockLog'] = array();

		try{
			if (!empty($options[0]) && is_array($options[0])) {
				foreach ($options as $option) {
					$data['StockLog'][] = $option;
				}
			} else {
				$data['StockLog'][] = $options;
			}
			$response = Requests::post($this->getConstant('SFAPI_URL').'/stock_items/addstockmovement', $this->headers, array('data' => json_encode($data)), array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function addStockItem($options) {
		try{	
			$data['StockItem'] = $options;
			$response = Requests::post($this->getConstant('SFAPI_URL').'/stock_items/add', $this->headers, array('data' => json_encode($data)), array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function stockItemEdit($options) {
		try{
			$data['StockItem'] = $options;
			$response = Requests::post($this->getConstant('SFAPI_URL').'/stock_items/edit', $this->headers, array('data' => json_encode($data)), array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function stockItems($params = array(), $list_info = true) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/stock_items/index.json'.$this->_getRequestParams($params, $list_info), $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}
	
	public function setInvoiceLanguage($id, $lang = 'slo') {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/invoices/setinvoicelanguage/'.$id.'/lang:'.$lang, $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function markAsSent($invoice_id, $email, $subject = '', $message = '') {
		try{
			$request_data['InvoiceEmail'] = array(
				'invoice_id' => $invoice_id,
				'email' 	 => $email,
				'subject' 	 => $subject,
				'message' 	 => $message,
			);
			$response = Requests::post($this->getConstant('SFAPI_URL').'/invoices/mark_as_sent', $this->headers, array('data' => json_encode($request_data)), array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function sendInvoiceEmail($options) {
		try{
			$request_data['Email'] = $options;

			$response = Requests::post($this->getConstant('SFAPI_URL').'/invoices/send', $this->headers, array('data' => json_encode($request_data)), array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function sendInvoicePost($options) {
		try{
			$request_data['Post'] = $options;

			$response = Requests::post($this->getConstant('SFAPI_URL').'/invoices/post', $this->headers, array('data' => json_encode($request_data)), array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function payInvoice($invoice_id, $amount = null, $currency = 'EUR', $date = null, $payment_type = 'transfer', $cash_register_id = null) {
		try{
			if (is_null($date)) {
				$date = date('Y-m-d');
			}

			$request_data['InvoicePayment'] = array(
				'invoice_id' => $invoice_id,
				'payment_type' => $payment_type,
				'amount' => $amount,
				'currency' => $currency,
				'created' => date('Y-m-d', strtotime($date)),
				'cash_register_id' => $cash_register_id
			);

			$response = Requests::post($this->getConstant('SFAPI_URL').'/invoice_payments/add/ajax:1/api:1', $this->headers, array('data' => json_encode($request_data)), array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}
	
	public function addContactPerson($data) {
		try{
			$request_data['ContactPerson'] = $data;
			$response = Requests::post(
				$this->getConstant('SFAPI_URL').'/contact_people/add/api:1', 
				$this->headers,
				array('data' => json_encode($request_data)), 
				array('timeout' => $this->timeout)
			);
			return json_decode($response->body);
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}			
	}

	public function payExpense($expense_id, $amount, $currency = null, $date = null, $payment_type = 'transfer', $cash_register_id = null) {
		try{
			if (is_null($date)) {
				$date = date('Y-m-d');
			}

			$request_data['ExpensePayment'] = array(
				'expense_id' => $expense_id,
				'payment_type' => $payment_type,
				'amount' => $amount,
				'currency' => $currency,
				'created' => date('Y-m-d', strtotime($date)),
				'cash_register_id' => $cash_register_id
			);

			$response = Requests::post($this->getConstant('SFAPI_URL').'/expense_payments/add', $this->headers, array('data' => json_encode($request_data)), array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function save() {
		try{
			if (empty($this->data['Expense'])) {
				$response = Requests::post($this->getConstant('SFAPI_URL').'/invoices/create', $this->headers, array('data' => json_encode($this->data)), array('timeout' => $this->timeout));
			} else {
				$response = Requests::post($this->getConstant('SFAPI_URL').'/expenses/add', $this->headers, array('data' => json_encode($this->data)), array('timeout' => $this->timeout));
			}
			$response_data = json_decode($response->body);

			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function edit() {
		try{
			if (empty($this->data['Expense'])) {
				$response = Requests::post($this->getConstant('SFAPI_URL').'/invoices/edit', $this->headers, array('data' => json_encode($this->data)), array('timeout' => $this->timeout));
			} else {
				$response = Requests::post($this->getConstant('SFAPI_URL').'/expenses/edit', $this->headers, array('data' => json_encode($this->data)), array('timeout' => $this->timeout));
			}

			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function saveClient() {
		try{
			$response = Requests::post($this->getConstant('SFAPI_URL').'/clients/create', $this->headers, array('data' => json_encode($this->data)), array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function setClient($key, $value = '') {
		return $this->_setData('Client', $key, $value);
	}

	public function setInvoice($key, $value = '') {
		$this->data['Expense'] = array();
		return $this->_setData('Invoice', $key, $value);
	}

	public function setExpense($key, $value = '') {
		$this->data['Invoice'] = array();
		$this->data['InvoiceItem'] = array();
		return $this->_setData('Expense', $key, $value);
	}
	
	public function setMyData($key, $value = '') {
        	return $this->_setData('MyData', $key, $value);
    	}

	public function getLogos() {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/users/logo', $this->headers, array('timeout' => $this->timeout));
			return json_decode($response->body);
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function getExpenseCategories() {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/expenses/expense_categories', $this->headers, array('timeout' => $this->timeout));
			return json_decode($response->body);
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function register($email, $send_email = true) {
		try{
			$request_data['User'] = array(
				'email' => $email,
				'send_email' => $send_email
			);
			$response = Requests::post($this->getConstant('SFAPI_URL').'/users/create', $this->headers, array('data' => json_encode($request_data)), array('timeout' => $this->timeout));
			return json_decode($response->body);
		}		
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function setInvoiceSettings($settings) {
		try{
			if (is_array($settings)) {
				$this->data['InvoiceSetting']['settings'] = json_encode($settings);
			}
		}
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function setInvoiceExtras($extras) {
		try{
			if (is_array($extras)) {
				$this->data['InvoiceExtra'] = $extras;
			}
		}
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function deleteInvoicePayment($payment_id) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/invoice_payments/delete/'.$payment_id, $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function deleteExpensePayment($payment_id) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/expense_payments/delete/'.$payment_id, $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function getCourierData($courier_type, $data) {
		try{
			if (empty($courier_type)) {
				throw new Exception("Empty courier type");
			}
			elseif (!in_array($courier_type, array('slp','csp'))) {
				throw new Exception("Wrong courier type");
			}
			if (empty($data)) {
				throw new Exception("Empty data");
			}
			$response = Requests::post($this->getConstant('SFAPI_URL').'/'.$courier_type.'_exports/export', $this->headers, array('data' => json_encode($data)), array('timeout' => $this->timeout));
			$result = json_decode($response->body);
			$result->data = base64_decode($result->data);
			return $result;
		}
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function cashRegister($cash_register_id, $params = array()) {
		try{
			$response = Requests::get($this->getConstant('SFAPI_URL').'/cash_register_items/index/'.$cash_register_id. $this->_getRequestParams($params), $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}

	public function sendSMS($data) {
		try{
			$response = Requests::post($this->getConstant('SFAPI_URL').'/sms/send', $this->headers, array('data' => json_encode($data)), array('timeout' => $this->timeout));	
			$response_data = json_decode($response->body);
			return $response_data;
		}
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}
	
	public function getInvoiceDetails($ids) {
		try{
			$ids = is_array($ids) ? $ids : [$ids]; 
			$response = Requests::get($this->getConstant('SFAPI_URL').'/invoices/getInvoiceDetails/'.implode(',', $ids), $this->headers, array('timeout' => $this->timeout));
			$response_data = json_decode($response->body);
			return $response_data;
		}
		catch (Exception $e) {
			return $this->exceptionHandling($e); 
		}
	}
	
	public function getUserCompaniesData($getAllCompanies = false) {
		try {
		    $response = Requests::get($this->getConstant('SFAPI_URL').'/users/getUserCompaniesData/'.$getAllCompanies, $this->headers, array('timeout' => $this->timeout));
		    $response_data = json_decode($response->body);
		    return $response_data;
		} catch (Exception $e) {
		    return $this->exceptionHandling($e);
		}
        }
}

class SFAPIclientCZ extends SFAPIclient{
	const
		SFAPI_URL = 'https://moje.superfaktura.cz';
}

class SFAPIclientAT extends SFAPIclient {
	const
		SFAPI_URL = 'http://meine.superfaktura.at';
}
