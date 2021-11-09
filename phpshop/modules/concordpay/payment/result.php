<?php

/**
 * ���������� ���������� � ����������� ������� (���������� callback �� �������� �������).
 */
session_start();
header('Content-Type: text/html; charset=utf-8');

$_classPath = '../../../';
include($_classPath . 'class/obj.class.php');
PHPShopObj::loadClass('base');
PHPShopObj::loadClass('lang');
PHPShopObj::loadClass('order');
PHPShopObj::loadClass('file');
PHPShopObj::loadClass('orm');
PHPShopObj::loadClass('payment');
PHPShopObj::loadClass('modules');
PHPShopObj::loadClass('system');
PHPShopObj::loadClass('parser');

$PHPShopBase    = new PHPShopBase($_classPath . 'inc/config.ini');
$PHPShopSystem  = new PHPShopSystem();
$PHPShopModules = new PHPShopModules($_classPath . 'modules/');
$PHPShopModules->checkInstall(ConcordPay::CONCORDPAY_PAYMENT_CODE);

class ConcordPayPayment extends PHPShopPaymentResult
{
    public function __construct()
    {
        include_once(__DIR__ . '/../class/ConcordPay.php');
        $this->payment_name = ConcordPay::CONCORDPAY_PAYMENT_NAME;
        $this->log = true;
        $this->ConcordPay = new ConcordPay();
        parent::__construct();
    }

    /**
     * ���������� ������ �� ������.
     */
    public function updateorder()
    {
        $response = $this->ConcordPay->getResponse();
        $orderId = $this->ConcordPay->getOrderId();

        if ($this->ConcordPay->checkResponse()) {
            $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['orders']);
            $PHPShopOrm->debug = $this->debug;

            $row = $PHPShopOrm->select(
                array('*'),
                array('uid' => "='" . $orderId . "'"),
                false,
                array('limit' => 1)
            );

            if (empty($row)) {
                $this->ConcordPay->log(
                    $response,
                    $orderId,
                    '����� ' . $orderId . ' �� ������',
                    '����������� � �������'
                );
            }

            if (isset($response['transactionStatus'])
                && $response['transactionStatus'] === ConcordPay::TRANSACTION_STATUS_APPROVED
            ) {
                if ($response['type'] === ConcordPay::RESPONSE_TYPE_PAYMENT) {
                    // Ordinary payment.
                    $this->ConcordPay->log(
                        $response,
                        $orderId,
                        "����� $orderId �������",
                        '����������� � �������'
                    );

                    // ��� �����
                    $PHPShopOrmPayment = new PHPShopOrm($GLOBALS['SysValue']['base']['payment']);
                    $PHPShopOrmPayment->insert([
                        'uid_new' => $row['uid'],
                        'name_new' => ConcordPay::CONCORDPAY_PAYMENT_NAME,
                        'sum_new' => $row['sum'],
                        'datas_new' => time()
                    ]);
                    // Order paid.
                    $status = $this->set_order_status_101();
                    $PHPShopOrm->update(
                        [
                            'statusi_new' => $status,
                            'paid_new' => 1,
                        ],
                        ['uid' => '="' . $row['uid'] . '"']
                    );
                } elseif ($response['type'] === ConcordPay::RESPONSE_TYPE_REVERSE) {
                    // Refunded payment.
                    $this->ConcordPay->log(
                        $response,
                        $orderId,
                        "����� �� ������ $orderId ���������",
                        '����������� � �������'
                    );
                    // Order canceled.
                    $status = 1;
                    // ��������� ������� �������.
                    $PHPShopOrm->debug = $this->debug;
                    $PHPShopOrm->update(
                        [
                            'statusi_new' => $status,
                            'paid_new' => 1,
                            'status_new' => serialize(['maneger' => "����� �� ������ $orderId ���������"])
                        ],
                        ['uid' => '="' . $row['uid'] . '"']
                    );
                }
            } else {
                $this->ConcordPay->log(
                    $response,
                    $orderId,
                    '������ ������ ������ ' . $orderId,
                    '����������� � �������'
                );
            }
        }
    }
}

new ConcordPayPayment();
