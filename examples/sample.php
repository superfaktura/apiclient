<?php
	DEFINE('SFAPI_EMAIL', 'example@example.com'); 		 // LOGIN EMAIL TO SUPERFAKTURA
	DEFINE('SFAPI_KEY', 'apikey'); 				  		 // SFAPI KEY
	DEFINE('SFAPI_MODULE', 'API'); 				  		 // TITLE OF MODULE FE. 'WOOCOMMERCE MODULE'
	DEFINE('SFAPI_APPTITLE', 'Example API application'); // TITLE OF YOUR APPLICATION FE. 'SUPERFAKTURA.SK'
	DEFINE('COMPANY_ID', 1); // COMPANY_ID (optional)

	require_once('../SFAPIclient/SFAPIclient.php');

	// Create and init SFAPIclient
	$api = new SFAPIclient(SFAPI_EMAIL, SFAPI_KEY, SFAPI_APPTITLE, SFAPI_MODULE, COMPANY_ID);

	//setup client data
	$api->setClient(array(
		'name'    => 'John Doe',
		'ico'     => '12345678',
		'dic'     => '12345678',
		'ic_dph'  => 'SK12345678',
		'email'   => 'john@doe.com',
		'address' => 'John\'s address',
		'city'    => 'New York',
		'zip'     => '123 30',
		'phone'   => '+1 234 567 890',
	));

	//setup invoice data
	$api->setInvoice(array(
		//all items are optional, if not used, they will be filled automatically
		'name'                 => 'My invoice',
		'variable'             => '123456',					//variable symbol / reference
		'constant'             => '0308',					//constant symbol
		'specific'             => '2012', 					//specific symbol
		'already_paid'         => true, 				//has the invoices been already paid?
		'comment'              => 'My comment',
	));

	//add invoice item, this can be called multiple times
	//if you are not a VAT registered, use tax = 0
	$api->addItem(array(
		'name'        => 'Superfaktura.sk',
		'description' => 'Subscriptions',
		'quantity'    => 1,
		'unit'        => 'ks',
		'unit_price'  => 40.83,
		'tax'         => 20
	));

	//save invoice
	$response = $api->save();

	// response object contains data about created invoices, or error messages respectively
	if($response->error === 0){
		//complete information about created invoice
		var_dump($response->data);
	} else {
		//error descriptions
		var_dump($response->error_message);
	}

?>
