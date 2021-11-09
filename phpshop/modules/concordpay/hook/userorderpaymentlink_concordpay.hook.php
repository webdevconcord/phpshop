<?php

include_once 'phpshop/modules/concordpay/class/ConcordPay.php';

/**
 * Функция хук, вывод информации о состоянии заказа, а также статуса платежа в платежном шлюзе.
 *
 * Примечание:
 * Нативная роль данного хука заключается в генерации кнопки оплаты. Однако платёжная система ConcordPay
 * не предусматривает возможности приёма платежей с одним и тем же order_id, поэтому указанный функционал заменён
 * запросом проверки состояния платежа внутри платёжной системы и его отображением.
 *
 * @param object $obj Объект функции
 * @param array $PHPShopOrderFunction Данные о заказе
 */
function userorderpaymentlink_mod_concordpay_hook($obj, $PHPShopOrderFunction)
{
    global $PHPShopSystem;

    $concordpay = new ConcordPay();
    $currencyISO = $PHPShopSystem->getDefaultValutaIso();
    $return = '';

    // Отображение подробностей заказа (в т.ч. запроса к платёжной системе о статусе платежа).
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
                // Вывод результата запроса к платёжной системе о статусе платежа.
                $message = PHPShopText::div(
                    __('Статус оплаты в платёжной системе: ') . PHPShopText::b($response['transactionStatus'])
                );
                $return .= $message;
            }
        } elseif ((int)$PHPShopOrderFunction->getSerilizeParam('orders.Person.order_metod') === ConcordPay::CONCORDPAY_PAYMENT_SYSTEM_ID) {
            $return = 'Заказ обрабатывается менеджером';
        }
    }
    return $return;
}

$addHandler = array('userorderpaymentlink' => 'userorderpaymentlink_mod_concordpay_hook');
