<?php

include_once 'phpshop/modules/concordpay/class/ConcordPay.php';

/**
 * ������� ���, ����� ���������� � ��������� ������, � ����� ������� ������� � ��������� �����.
 *
 * ����������:
 * �������� ���� ������� ���� ����������� � ��������� ������ ������. ������ �������� ������� ConcordPay
 * �� ��������������� ����������� ����� �������� � ����� � ��� �� order_id, ������� ��������� ���������� ������
 * �������� �������� ��������� ������� ������ �������� ������� � ��� ������������.
 *
 * @param object $obj ������ �������
 * @param array $PHPShopOrderFunction ������ � ������
 */
function userorderpaymentlink_mod_concordpay_hook($obj, $PHPShopOrderFunction)
{
    global $PHPShopSystem;

    $concordpay = new ConcordPay();
    $currencyISO = $PHPShopSystem->getDefaultValutaIso();
    $return = '';

    // ����������� ������������ ������ (� �.�. ������� � �������� ������� � ������� �������).
    /** @var PHPShopOrderFunction $PHPShopOrderFunction */
    if ((int)$PHPShopOrderFunction->order_metod_id === ConcordPay::CONCORDPAY_PAYMENT_SYSTEM_ID) {
        if ($PHPShopOrderFunction->getParam('statusi') === $concordpay->option['status_checkout']
            || empty($concordpay->option['status_checkout'])
        ) {
            $order = (array)$PHPShopOrderFunction->unserializeParam('orders');

            $concordpay->option['currency']   = $currencyISO;
            $concordpay->option['order_id']   = 'order_' . $order['Person']['ouid'];
            $concordpay->option['order_desc'] = 'Order ' . $order['Person']['ouid'];
            $concordpay->option['amount']     = $PHPShopOrderFunction->getTotal();

            $sign_raw = $concordpay->option['merchant_id'] . ';' . $concordpay->option['order_id'];
            $concordpay->option['signature'] = hash_hmac('md5', $sign_raw, $concordpay->option['password']);

            $response = $concordpay->sendQuery($concordpay->option);
            if (isset($response['transactionStatus']) && !empty($response['transactionStatus'])) {
                $return = ParseTemplateReturn(
                    $GLOBALS['SysValue']['templates']['concordpay']['concordpay_payment_forma'],
                    true
                );
                // ����� ���������� ������� � �������� ������� � ������� �������.
                $message = PHPShopText::div(
                    __('������ ������ � �������� �������: ') . PHPShopText::b($response['transactionStatus'])
                );
                $return .= $message;
            }
        } elseif ((int)$PHPShopOrderFunction->getSerilizeParam('orders.Person.order_metod') === ConcordPay::CONCORDPAY_PAYMENT_SYSTEM_ID) {
            $return = '����� �������������� ����������';
        }
    }
    return $return;
}

$addHandler = array('userorderpaymentlink' => 'userorderpaymentlink_mod_concordpay_hook');
