<?php
	$bench = true; // true = dump result | false = skip dump
	$time_round = 4; 
	$tstart = microtime(true); 
	
	/*******************************************
	 * SFAPI v.: 1.1 example
	 *******************************************
	 * info@superfaktura.sk
	 *******************************************/
	 
	DEFINE('SFAPI_EMAIL', 'example@example.com'); 		 // LOGIN EMAIL TO SUPERFAKTURA
	DEFINE('SFAPI_KEY', 'apikey'); 				  		 // SFAPI KEY
	DEFINE('SFAPI_MODULE', 'API'); 				  		 // TITLE OF MODULE FE. 'WOOCOMMERCE MODULE'
	DEFINE('SFAPI_APPTITLE', 'Example API application'); // TITLE OF YOUR APPLICATION FE. 'SUPERFAKTURA.SK'
	DEFINE('COMPANY_ID', 1); // COMPANY_ID (optional)

	require_once('../SFAPIclient/SFAPIclient.php');

	// Create and init SFAPIclient
	$api = new SFAPIclient(SFAPI_EMAIL, SFAPI_KEY, SFAPI_APPTITLE, SFAPI_MODULE, COMPANY_ID);

	/***********************************************
	 * Example; create new invoice
	 ***********************************************/
	// 1) set Client
	$api->setClient(array(
		'name' => 'Example s.r.o.', 					// Client name
		'phone' => '+421000000000',						// Client phone
		'email' => 'exampleclient@example.com'			// Client email
	));

	// 2) set Invoice
	$api->setInvoice(array(
		'name' => 'Invoice example', 					// Invoice name
		'variable' => '123456', 						// Variable
		'due' => date('Y-m-d', strtotime("+20 day")), 	// Due date
		'already_paid' => true 							// If true invoice is paid
	));

	// 3) add new Invioce item
	$api->addItem(array(
		'name' => 'Example item',							// Invoice item name
		'descriptions' => 'Description of "Example item"',	// Invoice item description
		'unit_price' => 20,									// Unit price without vat
		'tax' => 20,										// Vat
		'quantity' => 5,									// Quantity
		'discount' => 50,									// Invoice item discount in %
		'discount_description' => 'Discount 50% COUPON: AA11BB22CC33'
	));

	// 4) save data
	$response = $api->save();

	if (!empty($bench)) {
		_debug($response, 'Create new invoice example');
	}

	/********************************************
	 * Example; create new expense
	 ********************************************/
	// 1) Reset data
	$api->resetData();

	// 2) Set new Expense
	$api->setExpense(array(
		'name' => 'Example expense',	// Expense name
		'variable' => 123456,			// Variable
		'amount' => 100,				// Price no vat
		'vat' => 20,					// Vat
		'already_paid' => true 			// Expense is allready paid
	));

	// 3) Save Expense
	$response = $api->save();

	if (!empty($bench)) {
		_debug($response, 'Create new expense example');
	}

	/********************************************
	 * Example; edit invoice
	 ********************************************/
	$invoice_id = 0; // existing invoice id; Invoice->id

	if (!empty($invoice_id)) {
		// 1) Do not update Client
		$api->setClient(array());
		
		// 2) Set invoice data to update
		$api->setInvoice(array(
			'id' => $invoice_id,
			'name' => 'Example invoice "EDIT EXAMPLE"',
			'comment' => 'Example comment "EDIT EXAMPLE"',
			'variable' => 333
		));

		// 3) Add new InvoiceItem
		$api->addItem(array(
			'name' => 'New example item',
			'description' => 'Example item created from edit',
			'unit_price' => round(M_PI, 2),
			'quantity' => 2
		));

		// 4) Update invoice data
		$response = $api->edit();

		if (!empty($bench)) {
			_debug($response, 'Edit invoice example');
		}
	}

	/*********************************************
	 * Example; editing example expense
	 *********************************************/
	$expense_id = 0;

	if (!empty($expense_id)) {
		// 1) Set new Client
		$api->setClient(array(
			'name' => 'Example client (test)',
			'ico' => mt_rand(10000000, 99999999),
			'dic' => '012345678',
			'email' => 'mynewexampleclient@example.com'
		));

		// 2) Set Expense data to update
		$api->setExpense(array(
			'id' => $expense_id,
			'name' => 'Expense example EDIT',
			'amount' => round(M_E * 100),
			'currency' => 'CZK'
		));

		// 3) Update Expense data
		$response = $api->edit();

		if (!empty($bench)) {
			_debug($response, 'Edit expense example');
		}
	}

	/********************************************
	 * Example; send invoice by email
	 * , post
	 ********************************************/
	if (!empty($invoice_id)) {
		$response = $api->sendInvoiceEmail(array(
			'invoice_id' => $invoice_id,
			'to' => 'diamonjohn@gmail.com',
			'cc' => array(
				'example2@example.com',
				'example3@example.com'
			),
			'bcc' => array(
				'example4@example.com'
			)
			// , 'subject' => 'From API'
			// , 'body' => 'Ostra faktura test'
		));

		if (!empty($bench)) {
			_debug($response, 'Send email example');
		}

		$send_invoice_post = false;
		if ($send_invoice_post) {
			$response = $api->sendInvoicePost(array(
				'invoice_id' => $invoice_id,
				/*
				// uncomment to set custom delivery address
				'delivery_address' => 'Address 333',
				'delivery_city' => 'MyCity',
				'delivery_zip' => '94911',
				'delivery_country' => 'Slovenska republika'	
				*/
			));

			if (!empty($bench)) {
				_debug($response, 'Send invoice post');
			}
		}
	}

	/*******************************************
	 * Example; update invoice item
	 *******************************************/
	$invoice_item_id = 0; // set to your item id; belongs to invoice

	if (!empty($invoice_id) && !empty($invoice_item_id)) {
		// 1) Empty client, do not update
		$api->setClient(array());

		// 2) Set invoice id
		$api->setInvoice(array(
			'id' => $invoice_id
		));

		// 3) Set item 
		$api->addItem(array(
			'id' => $invoice_item_id,
			'name' => '*Edited invoice item.',
			'unit_price' => round(M_E * 1000, 2),
			'quantity' => round(M_PI)
		));

		// 4) Update expense data
		$response = $api->edit();

		if (!empty($bench)) {
			_debug($response, 'Update invoice item');
		}
	}

	/*******************************************
	 * Example; pay invoice
	 *******************************************/
	if (!empty($invoice_id)) {
		// 1) Add Inovice payment
		$response = $api->payInvoice($invoice_id, 333);
		if (!empty($bench)) {
			_debug($response, 'Add invoice payment');
		}
	}

	/*******************************************
	 * Example; pay expense
	 *******************************************/
	if (!empty($expense_id)) {
		// 1) Add Expense payment
		$response = $api->payExpense($expense_id, 333);
		if (!empty($bench)) {
			_debug($response, 'Add expense payment.');
		}
	}

	/*******************************************
	 * Example; create new stock item
	 *******************************************/
	// 1) Add new stock item
	$response = $api->addStockItem(array(
		'name' => 'Stock item example',
		'description' => 'Example stock item description.',
		'sku' => 'STOCK'.mt_rand(100, 999).'ID',
		'unit_price' => round(M_PI, 2),
		'vat' => 20,
		'stock' => 10
	));

	if (!empty($bench)) {
		_debug($response, 'Add stock item');
	}

	/******************************************
	 * Pridame pohyb na sklade
	 ******************************************/
	$stock_item_id = 0;
	if (!empty($stock_item_id)) {
		// Add new stock movement
		$response = $api->addStockMovement(array(
			'stock_item_id' => $stock_item_id,
			'quantity' => 50, // fe. -50 negative movement
			'note' => 'Stock movement example from API'
		));

		if (!empty($bench)) {
			_debug($response, 'Add stock movement');
		}
	}

	$tend = microtime(true);
	if (!empty($bench)) {
		// output time
		echo '<div style="background:red;color:white;padding:10px;margin-top:10px;">Bench is ON. Microtime in secs ~ <b>'.round($tend - $tstart, $time_round).'s</b><br></div>';
	}

	/***********************************************
	 * dump in readable format
	 ***********************************************/
	function _debug($obj, $title = '') {
		if (!is_array($obj)) {
			$obj = json_decode(json_encode($obj), true);
		}
		if (!empty($title)) {
			echo "<h2>$title</h2>";
		}
		echo '<code style="white-space: pre;background:#FAFCAC;margin-top:10px;padding:15px;display:block;width:450px;">';
		print_r($obj);
		echo '</code>';
	}
?>
