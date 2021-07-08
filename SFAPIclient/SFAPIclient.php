<?php
/**
 * @category   SuperFaktura API
 * @author     SuperFaktura.sk s.r.o. <info@superfaktura.sk>
 * @version    1.29
 * @link https://github.com/superfaktura/docs
 * @lastUpdate 21.08.2020
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
        $timeout = 30,
        $last_error = array(),
        $checksum = null,
        $use_sandbox = false;

    public
        $data = array(
            'Invoice' => array(),
            'Expense' => array(),
            'Client' => array(),
            'InvoiceItem' => array(),
            'MyData' => array(),
            'ExpenseItem' => array(),
        );

    const
        API_AUTH_KEYWORD = 'SFAPI',
        SFAPI_URL = 'https://moja.superfaktura.sk',
        SANDBOX_URL = 'https://sandbox.superfaktura.sk';

    public function useSandBox()
    {
        $this->use_sandbox = true;
    }

    public function __construct($email, $apikey, $apptitle = '', $module = 'API', $company_id = '')
    {
        Requests::register_autoloader();

        $this->className  = get_class($this);
        $this->email      = $email;
        $this->apikey     = $apikey;
        $this->company_id = $company_id;
        $this->headers    = array(
            'Authorization' => self::API_AUTH_KEYWORD." " . http_build_query(array('email' => $this->email, 'apikey' => $this->apikey, 'company_id' => $this->company_id, 'module' => $module))
        );
        $this->data['apptitle'] = $apptitle;
    }

    /**
    * set data
    */
    private function _setData($dataSet, $key, $value)
    {
        if (is_array($key)) {
            $this->data[$dataSet] = array_merge($this->data[$dataSet], $key);
            if (empty($key)) {
                $this->data[$dataSet] = array();
            }
        } else {
            $this->data[$dataSet][$key] = $value;
        }
    }

    /**
     * parse request params
     *
     * @param array $params
     * @param bool $list_info
     *
     * @return string
     */
    private function _getRequestParams($params, $list_info = true)
    {
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

    /**
     * handling exception
     * @param Exception $e
     * @return stdClass
     */
    protected function exceptionHandling(Exception $e)
    {
        $response_data = new stdClass();
        $response_data->error = 99;
        $response_data->error_message = $e->getMessage();

        return $response_data;

    }

    /**
     * reset data
     *
     * @param array $options 
     */
    public function resetData($options = array())
    {
        if (empty($options)) {
            $options = array('Invoice', 'InvoiceItem', 'Expense', 'Client', 'ExpenseItem');
        }
        foreach ($options as $option) {
            $this->data[$option] = array();
        }

        $this->checksum = null;
    }

    /**
     * Set invoice item
     *
     * @param array $item
     * @param string $item_type
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#invoiceitem-1 
     */
    public function addItem($item = array(), $item_type = 'Invoice')
    {
        $this->data[$item_type === 'Expense' ? 'ExpenseItem' : 'InvoiceItem'][] = $item;
    }

    /**
     * Delete existing invoice item
     *
     * @param int $invoice_id
     * @param int $item_id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#delete-invoice-item
     */
    public function deleteInvoiceItem($invoice_id, $item_id)
    {
        if (!is_array($item_id)) {
            $item_id = array($item_id);
        }

        return $this->get('/invoice_items/delete/' . implode(",", $item_id) . '/invoice_id:' . $invoice_id);
    }

    /**
     * Delete existing expense
     *
     * @param int $id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/expenses.md#delete-expense
     */
    public function deleteExpense($id)
    {
        return $this->get('/expenses/delete/' . $id);
    }

    /**
     * Add tags to invoice create
     *
     * @param array $tag_ids
     */
    public function addTags($tag_ids = array())
    {
        $this->data['Tag']['Tag'] = $tag_ids;
    }

    /**
     * Get list of clients
     *
     * @param array $params
     * @param bool $list_info
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/clients.md#get-client-list
     */
    public function clients($params = array(), $list_info = true)
    {
        return $this->get('/clients/index.json' . $this->_getRequestParams($params, $list_info));
    }

    /**
     * Get client detail
     *
     * @param int $id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/clients.md#view-client-detail
     */
    public function client($id)
    {
        return $this->get('/clients/view/' . $id);
    }

    /**
     * Delete existing invoice
     *
     * @param int $id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#delete-invoice
     */
    public function delete($id)
    {
        return $this->get('/invoices/delete/' . $id);
    }

    /**
     * Delete existing stock item
     *
     * @param int $id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/stock.md#delete-stock-item
     */
    public function deleteStockItem($id)
    {
        return $this->get('/stock_items/delete/' . $id);
    }

    /**
     * Get list of expenses
     *
     * @param array $params
     * @param bool $list_info
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/expenses.md#get-list-of-expenses
     */
    public function expenses($params = array(), $list_info = true)
    {
        return $this->get('/expenses/index.json' . $this->_getRequestParams($params, $list_info));
    }

    /**
     * Get constant
     *
     * @param string $const
     *
     * @return mixed
     */
    protected function getConstant($const)
    {
        if ($const === 'SFAPI_URL' && $this->use_sandbox) {
            return static::SANDBOX_URL;
        }

        return constant(get_class($this)."::".$const);
    }

    /**
     * Get list of countries
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/value-lists.md#country-list
     */
    public function getCountries()
    {
        return $this->get('/countries');
    }

    /**
     * Get list of sequences
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/value-lists.md#sequences
     */
    public function getSequences()
    {
        return $this->get('/sequences/index.json');
    }

    /**
     * Get list of tags
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/tags.md#get-list-of-tags
     */
    public function getTags()
    {
        return $this->get('/tags/index.json');
    }

    /**
     * Get pdf
     *
     * @param int $invoice_id
     * @param string $token
     * @param string $language
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#get-invoice-pdf
     */
    public function getPDF($invoice_id, $token, $language = 'slo')
    {
        return $this->get('/'.$language.'/invoices/pdf/'.$invoice_id.'/token:'.$token, false);
    }

    /**
     * Get invoice detail
     *
     * @param int $id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#get-invoice-detail
     */
    public function invoice($id)
    {
        return $this->get('/invoices/view/'.$id.'.json');
    }

    /**
     * Get list of invoices
     *
     * @param array $params
     * @param bool $list_info
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#get-list-of-invoices
     */
    public function invoices($params = array(), $list_info = true)
    {
        return $this->get('/invoices/index.json'.$this->_getRequestParams($params, $list_info));
    }

    /**
     * Get expense detail
     *
     * @param int $id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/expenses.md#expense-detail
     */
    public function expense($id)
    {
        return $this->get('/expenses/edit/'.$id.'.json');
    }

    /**
     * Get stock item detail
     *
     * @param int $id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/stock.md#view-stock-item-details
     */
    public function stockItem($id)
    {    
        return $this->get('/stock_items/view/' . $id);
    }

    /**
     * Add stock movement
     *
     * @param array $options
     * 
     * @return mixed|stdClass
     * 
     * @link https://github.com/superfaktura/docs/blob/master/stock.md#add-stock-movement
     */
    public function addStockMovement($options)
    {
        $data = array();
        $data['StockLog'] = array();

        if (!empty($options[0]) && is_array($options[0])) {
            foreach ($options as $option) {
                $data['StockLog'][] = $option;
            }
        } else {
            $data['StockLog'][] = $options;
        }

        return $this->post('/stock_items/addstockmovement', $data);
    }

    /**
     * Add stock item
     *
     * @param array $options
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/stock.md#add-stock-item
     */
    public function addStockItem($options)
    {
        $data['StockItem'] = $options;
        return $this->post('/stock_items/add', $data);
    }

    /**
     * Edit stock item
     * 
     * @param array $options
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/stock.md#edit-stock-item
     */
    public function stockItemEdit($options)
    {
        $data['StockItem'] = $options;
        return $this->post('/stock_items/edit', $data);
    }

    /**
     * Get list of stock items
     *
     * @param array $params
     * @param bool $list_info
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/stock.md#get-list-of-stock-items
     */
    public function stockItems($params = array(), $list_info = true)
    {
        return $this->get('/stock_items/index.json'.$this->_getRequestParams($params, $list_info));
    }

    /**
     * set language for invoice
     *
     * @param int $id
     * @param string $lang
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#set-invoice-language
     */
    public function setInvoiceLanguage($id, $lang = 'slo')
    {
        return $this->get('/invoices/setinvoicelanguage/'.$id.'/lang:'.$lang);
    }

    /**
     * Mark invoice as sent via email
     *
     * @param int $invoice_id
     * @param string $email
     * @param string $subject
     * @param string $message
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#mark-invoice-as-sent-via-email
     */
    public function markAsSent($invoice_id, $email, $subject = '', $message = '')
    {

        $request_data['InvoiceEmail'] = array(
            'invoice_id' => $invoice_id,
            'email'      => $email,
            'subject'      => $subject,
            'message'      => $message,
        );

        return $this->post('/invoices/mark_as_sent', $request_data);

    }

    /**
     * Send invoice via email
     *
     * @param array $options
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#send-invoice-via-mail
     */
    public function sendInvoiceEmail($options)
    {
        $request_data['Email'] = $options;
        return $this->post('/invoices/send', $request_data);
    }

    /**
     * Send invoice via post
     *
     * @param array $options
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#send-invoice-via-post
     */
    public function sendInvoicePost($options)
    {
        $request_data['Post'] = $options;

        return $this->post('/invoices/post', $request_data);
    }

    /**
     * Pay invoice
     *
     * @param int $invoice_id
     * @param int|float $amount
     * @param string $currency
     * @param null|string $date
     * @param string $payment_type
     * @param null|int $cash_register_id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#pay-invoice
     */
    public function payInvoice($invoice_id, $amount = null, $currency = 'EUR', $date = null, $payment_type = 'transfer', $cash_register_id = null)
    {
        if (is_null($date)) {
            $date = date('Y-m-d');
        }

        $request_data['InvoicePayment'] = array(
            'invoice_id'       => $invoice_id,
            'payment_type'     => $payment_type,
            'amount'           => $amount,
            'currency'         => $currency,
            'created'          => date('Y-m-d', strtotime($date)),
            'cash_register_id' => $cash_register_id
        );

        return $this->post('/invoice_payments/add/ajax:1/api:1', $request_data);
    }

    /**
     * Add new contact person to existing client
     *
     * @param array $data
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/contact-persons.md#add-contact-person-to-client
     */
    public function addContactPerson($data)
    {
        $request_data['ContactPerson'] = $data;
        return $this->post('/contact_people/add/api:1', $request_data);
    }

    /**
     * Pay expense
     *
     * @param int $expense_id
     * @param float $amount
     * @param null|string $currency
     * @param null|string $date
     * @param string $payment_type
     * @param null|int $cash_register_id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/expenses.md#add-expense-payment
     */
    public function payExpense($expense_id, $amount, $currency = null, $date = null, $payment_type = 'transfer', $cash_register_id = null)
    {
        if (is_null($date)) {
            $date = date('Y-m-d');
        }

        $request_data['ExpensePayment'] = array(
            'expense_id'       => $expense_id,
            'payment_type'     => $payment_type,
            'amount'           => $amount,
            'currency'         => $currency,
            'created'          => date('Y-m-d', strtotime($date)),
            'cash_register_id' => $cash_register_id
        );

        return $this->post('/expense_payments/add', $request_data);
    }

    /**
     * Save data 
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#add-invoice
     * @link https://github.com/superfaktura/docs/blob/master/expenses.md#add-expense
     */
    public function save()
    {
        if (empty($this->data['Expense'])) {
            return $this->post('/invoices/create', $this->data);
        } else {
            return $this->post('/expenses/add', $this->data);
        }
    }

    /**
     * Edit data 
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#edit-invoice
     * @link https://github.com/superfaktura/docs/blob/master/expenses.md#edit-expense
     */
    public function edit()
    {
        if (empty($this->data['Expense'])) {
            return $this->post('/invoices/edit', $this->data);
        } else {
            return $this->post('/expenses/edit', $this->data);
        }
    }

    /**
     * Add client
     * 
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/clients.md#create-client 
     */
    public function saveClient()
    {
        return $this->post('/clients/create', $this->data);
    }

    /**
     * Set client data
     *
     * @param string $key
     * @param mixed $value
     *  
     * @link https://github.com/superfaktura/docs/blob/master/clients.md#attributes
     */
    public function setClient($key, $value = '')
    {
        $this->_setData('Client', $key, $value);
    }

    /**
     * Set invoice data
     *
     * @param string $key
     * @param mixed $value
     *  
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#invoice-2
     */
    public function setInvoice($key, $value = '')
    {
        $this->data['Expense'] = array();
        $this->_setData('Invoice', $key, $value);
    }

    /**
     * Set expense data
     *
     * @param string $key
     * @param mixed $value
     *  
     * @link https://github.com/superfaktura/docs/blob/master/expenses.md#optional
     */
    public function setExpense($key, $value = '')
    {
        $this->data['Invoice'] = array();
        $this->data['InvoiceItem'] = array();
        $this->_setData('Expense', $key, $value);
    }

    /**
     * Set company data
     *
     * @param string $key
     * @param mixed $value
     *  
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#mydata
     */
    public function setMyData($key, $value = '')
    {
        $this->_setData('MyData', $key, $value);
    }

    /**
     * Get list of logos
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/value-lists.md#logos
     */
    public function getLogos()
    {
        return $this->get('/users/logo');
    }

    /**
     * Get list of expense categories
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/value-lists.md#expense-categories
     */
    public function getExpenseCategories()
    {
        return $this->get('/expenses/expense_categories');
    }

    /**
     * Create account
     *
     * @param string $email
     * @param bool $send_email
     *
     * @return mixed|stdClass
     */
    public function register($email, $send_email = true)
    {
        $request_data['User'] = array(
            'email' => $email,
            'send_email' => $send_email
        );
        
        return $this->post('/users/create', $request_data);
    }

    /**
     * Set invoice setting
     *
     * @param array $settings  
     */
    public function setInvoiceSettings($settings)
    {
        if (is_array($settings)) {
            $this->data['InvoiceSetting']['settings'] = json_encode($settings);
        }
    }

    /**
     * Set invoice extras
     *
     * @param array $extras
     */
    public function setInvoiceExtras($extras)
    {
        if (is_array($extras)) {
            $this->data['InvoiceExtra'] = $extras;
        }
    }

    /**
     * Delete existing invoice payment
     *
     * @param int $payment_id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#delete-invoice-payment
     */
    public function deleteInvoicePayment($payment_id)
    {
        return $this->get('/invoice_payments/delete/' . $payment_id);
    }

    /**
     * Delete existing expense payment
     *
     * @param int $payment_id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/expenses.md#delete-expense-payment
     */
    public function deleteExpensePayment($payment_id)
    {
        return $this->get('/expense_payments/delete/' . $payment_id);
    }

    /**
     * Get courier data
     *
     * @param string $courier_type
     * @param array $data
     *
     * @return mixed|stdClass
     */
    public function getCourierData($courier_type, $data)
    {

       if (!in_array($courier_type, array('slp','csp'))) {
            $this->last_error = array(
                'status' => 404,
                'message' => 'Wrong courier type',
            );
            return null;
        } elseif (empty($data)) {
           $this->last_error = array(
                'status' => 404,
                'message' => 'Empty data',
            );
           return null;
        }

        $result = $this->post('/'.$courier_type.'_exports/export', $data);
        $result->data = base64_decode($result->data);
        return $result;
    }

    /**
     * Get detailed information about cash register and its items
     *
     * @param int $cash_register_id
     * @param array $params 
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/cash-register-item.md#get-cash-register-items
     */
    public function cashRegister($cash_register_id, $params = array())
    {
        return $this->get('/cash_register_items/index/'.$cash_register_id. $this->_getRequestParams($params));
    }

    /**
     * Send SMS reminder
     *
     * @param array $options
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/3d896497c01def0d69ea4281c7c48b3618495770/other.md#send-sms-reminder
     */
    public function sendSMS($data)
    {
        return $this->post('/sms/send', $data);
    }

    /**
     * Get information about multiple invoices at once. You can specify up to 100 invoice IDs
     *
     * @param int|array $ids
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/invoice.md#get-invoice-details
     */
    public function getInvoiceDetails($ids)
    {
        $ids = is_array($ids) ? $ids : array($ids);
        return $this->get('/invoices/getInvoiceDetails/'.implode(',', $ids));
    }

    /**
     * Get information about company in which user is currently logged in
     *
     * @param bool $getAllCompanies
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/other.md#get-user-companies-data
     */
    public function getUserCompaniesData($getAllCompanies = false)
    {
        return $this->get('/users/getUserCompaniesData/' . $getAllCompanies);
    }

    /**
     * Create regular form proforma invoice
     *
     * @param int $proforma_id
     *
     * @return mixed|stdClass
     */
    public function createRegularFromProforma($proforma_id)
    {
        if (empty($proforma_id)) {
            $this->last_error = array(
                'status' => 404,
                'message' => 'Item not found',
            ); 
            return null;
        }

        $proforma = $this->get('/invoices/regular.json/' . $proforma_id);

        if (empty($proforma)) {
            $this->last_error = array(
                'status' => 404,
                'message' => 'Item not found',
            ); 
            return null;
        }

        return $this->post('/invoices/create', (array)$proforma);
    }

    /**
     * Set estimate status
     *
     * @param int $estimate_id
     * @param int $status
     *
     * @return mixed|stdClass
     */
    public function setEstimateStatus($estimate_id, $status)
    {
        if (empty($estimate_id)) {
            $this->last_error = array(
                'status' => 404,
                'message' => 'Item not found',
            );
            return null;
        }

        if (empty($status)) {
            $this->last_error = array(
                'status' => 404,
                'message' => 'Estimate status not found',
            );
            return null;
        }

        return $this->get('/invoices/set_estimate_status/' . $estimate_id . '/' . $status . '/ajax:1');
    }

    /**
     * Get list of bank accounts
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/bank-account.md#get-list-of-bank-accounts
     */
    public function getBankAccounts()
    {
        return $this->get('/bank_accounts/index');
    }

    /**
     * Create new bank account
     *
     * @param array $data
     *
     * @return mixed|stdClass
     */
    public function addBankAccount($data)
    {
        return $this->post('/bank_accounts/add/', $data);
    }

    /**
     * Delete existing bank account
     *
     * @param int $id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/bank-account.md#delete-bank-account
     */
    public function deleteBankAccount($id)
    {
        return $this->get('/bank_accounts/delete/' . $id);
    }

    /**
     * Update existing bank account
     *
     * @param int $id
     * @param array $data
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/bank-account.md#update-bank-account
     */
    public function updateBankAccount($id, $data)
    {
        return $this->post('/bank_accounts/update/' . $id, $data);
    }

    /**
     * Create new tag.
     *
     * @param array $data
     *
     * @return mixed|stdClass
     * 
     * @link https://github.com/superfaktura/docs/blob/3d896497c01def0d69ea4281c7c48b3618495770/tags.md#add-tag
     */
    public function addTag(array $data)
    {
        return $this->post('/tags/add', $data);
    }

    /**
     * Delete existing tag.
     *
     * @param int $id
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/master/tags.md#delete-tag
     */
    public function deleteTag($id)
    {
        return $this->get('/tags/delete/' . $id);
    }

    /**
     * Edit existing tag.
     *
     * @param int $id
     * @param array $data
     *
     * @return mixed|stdClass
     *
     * @link https://github.com/superfaktura/docs/blob/3d896497c01def0d69ea4281c7c48b3618495770/tags.md#edit-tag
     */
    public function editTag($id, array $data)
    {
        return $this->post('/tags/edit/' . $id, $data);
    }

    /**
     * Get response by checksum.
     *
     * @param string $checksum
     *
     * @return mixed|stdClass
     */
    public function getResponseByChecksum($checksum)
    {
        return $this->get('/api_logs/getResponseByChecksum/' . $checksum);
    }

    /**
     * Handling GET request
     *
     * @param string $url
     * @param bool $json_decode
     *
     * @return mixed|stdClass
     */
    public function get($url, $json_decode = true)
    {
        try {
            $response = Requests::get(
                $this->getConstant('SFAPI_URL') . $url,
                $this->headers,
                array('timeout' => $this->timeout)
            );

            if (substr($response->status_code, 0, 1) != 2) {
                $error_message = json_decode($response->body);
                $this->last_error = array(
                    'status' => $response->status_code,
                    'message' => !empty($error_message->error_message) ? $error_message->error_message : '',
                );
                return null;
            }

            return !empty($json_decode) ? json_decode($response->body) : $response;

        } catch (Exception $e) {
            $this->last_error = array(
                'status' => 500,
                'message' => $this->exceptionHandling($e),
            );
            return null;
        }
    }

    /**
     * Handling POST request
     *
     * @param string $url
     * @param array $data
     *
     * @return mixed|stdClass
     */
    public function post($url, $data)
    {
        try {
            // Set actual checksum
            $this->setChecksumData($data);

            // Send checksum
            $data['checksum'] = $this->getChecksum();

            $response = Requests::post(
                $this->getConstant('SFAPI_URL') . $url,
                $this->headers,
                array('data' => json_encode($data)),
                array('timeout' => $this->timeout)
            );

            if (substr($response->status_code, 0, 1) != 2) {
                $error_message = json_decode($response->body);
                $this->last_error = array(
                    'status'   => $response->status_code,
                    'message'  => !empty($error_message->error_message) ? $error_message->error_message : '',
                    'checksum' => $this->getChecksum(),
                );
                return null;
            }

            return json_decode($response->body);

        } catch (Exception $e) {
            $this->last_error = array(
                'status'   => 500,
                'message'  => $this->exceptionHandling($e),
                'checksum' => $this->getChecksum(),
            );
            return null;
        }
    }

    /**
     * Set actual checksum.
     *
     * @param array $data
     */
    protected function setChecksumData($data)
    {
        $data['date'] = date('Y-m-d');

        $this->checksum = md5(json_encode($data));
    }

    /**
     * Get actual checksum.
     *
     * @return string|null
     */
    public function getChecksum()
    {
        return $this->checksum;
    }

    /**
     * Get last error
     *
     * @return array
     */
    public function getLastError()
    {
        return !empty($this->last_error) ? $this->last_error : array();
    }
}

class SFAPIclientCZ extends SFAPIclient
{
    const SFAPI_URL = 'https://moje.superfaktura.cz';
    const SANDBOX_URL = 'https://sandbox.superfaktura.cz';
}

class SFAPIclientAT extends SFAPIclient
{
    const SFAPI_URL = 'https://meine.superfaktura.at';
    const SANDBOX_URL = 'https://sandbox.superfaktura.sk';
}
