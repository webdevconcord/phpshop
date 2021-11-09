<?php

class ConcordPay
{
    // ID - Выдаётся индивидуально разработчиком PHPShop для каждой новой платёжной системы.
    public const CONCORDPAY_PAYMENT_SYSTEM_ID = 10116;

    public const FORM_ACTION = 'https://pay.concord.ua/api/';

    public const CONCORDPAY_CHECK_URL = 'https://pay.concord.ua/api/check';

    public const CONCORDPAY_PAYMENT_CODE = 'concordpay';
    public const CONCORDPAY_PAYMENT_NAME = 'ConcordPay';

    public const SIGNATURE_SEPARATOR = ';';
    public const PAYMENT_PAGE_LANGUAGE = 'ru';

    public const RESPONSE_TYPE_PAYMENT = 'payment';
    public const RESPONSE_TYPE_REVERSE = 'reverse';

    public const TRANSACTION_STATUS_APPROVED = 'Approved';
    public const TRANSACTION_STATUS_DECLINED = 'Declined';

    public const CONCORDPAY_RESULT_SUCCESS = 'success';
    public const CONCORDPAY_RESULT_FAIL    = 'fail';
    public const CONCORDPAY_RESULT_CANCEL  = 'cancel';

    public $option;

    /**
     * Array keys for generate request signature.
     *
     * @var string[]
     */
    protected $keysForRequestSignature = array(
        'merchant_id',
        'order_id',
        'amount',
        'currency_iso',
        'description',
    );

    /**
     * Array keys for generate response signature.
     *
     * @var string[]
     */
    protected $keysForResponseSignature = array(
        'merchantAccount',
        'orderReference',
        'amount',
        'currency',
    );

    /**
     * Array keys for generate request.
     *
     * @var string[]
     */
    protected $keysForRequest = array(
        'operation',
        'merchant_id',
        'amount',
        'order_id',
        'currency_iso',
        'description',
        'add_params',
        'approve_url',
        'decline_url',
        'cancel_url',
        'callback_url',
        'language',
        // Statistics.
        'client_last_name',
        'client_first_name',
        'email',
        'phone'
    );

    /**
     * Allowed response operation type.
     *
     * @var string[]
     */
    protected $allowedOperationTypes = [
        self::RESPONSE_TYPE_PAYMENT,
        self::RESPONSE_TYPE_REVERSE
    ];

    private $response;

    public function __construct()
    {
        $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['concordpay']['concordpay_system']);
        $this->option = $PHPShopOrm->select();
        $this->option['response_url'] = $this->urlSite() . '/done/';
        $this->option['server_callback_url'] = $this->urlSite() . '/success/';
    }

    /**
     * @param $option
     * @param $keys
     * @return false|string
     */
    public function getSignature($option, $keys)
    {
        $hash = array();
        foreach ($keys as $data_key) {
            if (!isset($option[$data_key])) {
                $option[$data_key] = '';
            }
            if (is_array($option[$data_key])) {
                foreach ($option[$data_key] as $v) {
                    $hash[] = $v;
                }
            } else {
                $hash [] = $option[$data_key];
            }
        }

        $hash = implode(self::SIGNATURE_SEPARATOR, $hash);

        return hash_hmac('md5', $hash, $this->option['password']);
    }

    /**
     * @param $options
     *
     * @return string
     */
    public function getRequestSignature($options)
    {
        return $this->getSignature($options, $this->keysForRequestSignature);
    }

    /**
     * @param $options
     *
     * @return string
     */
    public function getResponseSignature($options)
    {
        return $this->getSignature($options, $this->keysForResponseSignature);
    }

    /**
     * @param $message
     * @param $order_id
     * @param $status
     * @param $type
     */
    public function log($message, $order_id, $status, $type)
    {
        $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['concordpay']['concordpay_log']);
        $PHPShopOrm->insert(array(
            'message_new'  => serialize($message),
            'order_id_new' => $order_id,
            'status_new'   => $status,
            'type_new'     => $type,
            'date_new'     => time()
        ));
    }

    /**
     * @return string
     */
    public function getForm()
    {
        $payment_forma = '';
        foreach ($this->option as $fieldName => $field) {
            if (in_array($fieldName, $this->keysForRequest, true)) {
                $payment_forma .= PHPShopText::setInput(
                    'hidden',
                    $fieldName,
                    $this->option[$fieldName]
                );
            }
        }
        $payment_forma .= PHPShopText::setInput(
            'hidden',
            'signature',
            $this->getRequestSignature($this->option)
        );
        $payment_forma .= PHPShopText::setInput(
            'submit',
            'send',
            self::CONCORDPAY_PAYMENT_NAME,
            'left; margin-left:10px;',
            250
        );

        return $payment_forma;
    }

    /**
     * @return string
     */
    public function urlSite()
    {
        $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443)
            ? 'https://'
            : 'http://';

        return trim($protocol . $this->getSiteName(), '/');
    }

    /**
     * @return string
     */
    public function getSiteName()
    {
        $domainName = $_SERVER['HTTP_HOST'];

        return htmlspecialchars($domainName);
    }

    /**
     * @param $fullname
     * @return array
     */
    public function getClientNameSplitted($fullname)
    {
        $names = explode(' ', $fullname);
        if (is_array($names) && count($names) < 1) {
            throw new \InvalidArgumentException('Error: Wrong client name.');
        }

        $userNames = [];
        $userNames['client_first_name'] = $names[0] ?? '';
        $userNames['client_last_name']  = $names[1] ?? '';

        return $userNames;
    }

    /**
     * @return array|null
     */
    public function getClient()
    {
        $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['shopusers']);
        $login = null;

        if ($GLOBALS['SysValue']['other']['UsersLogin'] !== null) {
            $login = $GLOBALS['SysValue']['other']['UsersLogin'];
        } elseif (!empty($_POST) && isset($_POST['login_new']) && !empty($_POST['login_new'])) {
            $login = htmlspecialchars($_POST['login_new']);
        }

        $client = $PHPShopOrm->select(array('*'), array('login' => "='" . $login . "'"));
        if (!empty($client)) {
            return $client;
        }

        if (!empty($_POST)) {
            if (isset($_POST['name_new'])) {
                $client['name'] = htmlspecialchars($_POST['name_new']);
            }
            if (isset($_POST['mail_new'])) {
                $client['mail'] = htmlspecialchars($_POST['mail_new']);
            }
        }

        return $client;
    }

    /**
     * @return bool
     */
    public function checkResponse()
    {
        $response = json_decode(file_get_contents('php://input'), true);
        $errorMessage = '';
        $merchant_id = $this->option['merchant_id'];

        if (empty($response)) {
            $errorMessage .= 'Error: Server response is empty.' . PHP_EOL;
        }

        if (!isset($response['merchantAccount']) || $response['merchantAccount'] !== $merchant_id) {
            $errorMessage .= 'Error: The merchant\'s name does not match.' . PHP_EOL;
        }

        if (!isset($response['orderReference']) || empty($response['orderReference'])) {
            $errorMessage .= 'Error: Wrong Order ID.' . PHP_EOL;
        }

        if (!isset($response['amount']) || empty($response['amount'])) {
            $errorMessage .= 'Error: Wrong order amount.' . PHP_EOL;
        }

        if (!isset($response['currency']) || empty($response['currency'])) {
            $errorMessage .= 'Error: Wrong order currency.' . PHP_EOL;
        }

        if (!isset($response['type']) || !in_array($response['type'], $this->allowedOperationTypes, true)) {
            $errorMessage .= 'Error: Unknown operation type.' . PHP_EOL;
        }

        $signature = $this->getResponseSignature($response);
        if (!isset($response['merchantSignature']) || $response['merchantSignature'] !== $signature) {
            $errorMessage .= 'Error: Wrong signature.';
        }

        if ($errorMessage !== '') {
            die($errorMessage);
        }

        $this->response = $response;

        return true;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        if ($this->response === null) {
            $this->checkResponse();
        }

        return $this->response;
    }

    /**
     * @return mixed|string
     */
    public function getOrderId()
    {
        $response = $this->getResponse();
        $orderId = explode("_", $response['orderReference']);

        return $orderId[1] ?? '';
    }

    /**
     * @param $params
     * @return mixed
     */
    public function sendQuery($params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::CONCORDPAY_CHECK_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));

        return json_decode(curl_exec($ch), true);
    }
}
