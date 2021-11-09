<?php

/**
 * Функция хук, отображение результатов выполнения платежа.
 *
 * @param object $obj Объект функции
 * @param array $value Данные о заказе
 */
function success_mod_concordpay_hook($obj, $value)
{
    include_once 'phpshop/modules/concordpay/class/ConcordPay.php';

    if (isset($value['payment'], $value['result'], $value['order'])
        && $value['payment'] === ConcordPay::CONCORDPAY_PAYMENT_CODE) {
        /** @var PHPShopSuccess $obj */
        $obj->order_metod = 'modules" and id="10116';
        $obj->inv_id = $value['order'];

        if ($value['result'] === ConcordPay::CONCORDPAY_RESULT_SUCCESS) {
            $obj->message();
            return true;
        }

        if ($value['result'] === ConcordPay::CONCORDPAY_RESULT_FAIL
            || $value['result'] === ConcordPay::CONCORDPAY_RESULT_CANCEL) {
            $obj->error();
        }
    }
}

$addHandler = array('index' => 'success_mod_concordpay_hook');
