<?php
	// Create new contact person for existing client
	require_once('../SFAPIclient/SFAPIclient.php');
	$email = 'example@example.com';
	$token = 'apitoken';
	$api = new SFAPIclient($email, $token);
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
