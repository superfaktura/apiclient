<?php
	DEFINE('SFAPI_EMAIL', 'example@example.com'); 		 // LOGIN EMAIL TO SUPERFAKTURA
	DEFINE('SFAPI_KEY', 'apikey'); 				  		 // SFAPI KEY
	DEFINE('SFAPI_MODULE', 'API'); 				  		 // TITLE OF MODULE FE. 'WOOCOMMERCE MODULE'
	DEFINE('SFAPI_APPTITLE', 'Example API application'); // TITLE OF YOUR APPLICATION FE. 'SUPERFAKTURA.SK'
	DEFINE('COMPANY_ID', 1); // COMPANY_ID (optional)

	require_once('../SFAPIclient/SFAPIclient.php');

	// Create and init SFAPIclient
	$api = new SFAPIclient(SFAPI_EMAIL, SFAPI_KEY, SFAPI_APPTITLE, SFAPI_MODULE, COMPANY_ID);

	// Create new contact person for existing client
	$data = array(
		'client_id' => 503084,
		'name' => 'Jaroslav',
		'email' => 'jaro@gmail.com'
	);
	$result = $api->addContactPerson($data);
	if ($result->state === 'SUCCESS') {
		echo 'Contact person saved. ID: '.$result->data->ContactPerson->id;	
	} else {
		echo 'Error saving contact person: '.$result->message;	
	}

?>
