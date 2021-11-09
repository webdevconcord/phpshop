<?php

include_once 'phpshop/modules/concordpay/class/ConcordPay.php';

/**
 * Функция хук, генерация формы для редиректа на страницу оплаты ConcordPay.
 *
 * @param object $obj Объект функции
 * @param array $value Данные о заказе
 * @param string $rout Место внедрения хука ('START', 'MIDDLE', 'END')
 */
function send_concordpay_hook($obj, $value, $rout)
{
    global $PHPShopSystem;

    if ($rout === 'MIDDLE' && (int)$value['order_metod'] === ConcordPay::CONCORDPAY_PAYMENT_SYSTEM_ID) {
        $currencyISO = $PHPShopSystem->getDefaultValutaIso();
        $concordpay = new ConcordPay();

        $client = $concordpay->getClient();
        $clientNameFull = $client['name'] ?? '';
        $clientNameSplit = $concordpay->getClientNameSplitted($clientNameFull);
        $phone = $client['tel'] ?? '';
        $email = $client['mail'] ?? '';
        $description = __('Оплата картой на сайте') . ' ' . $concordpay->getSiteName() .
            ", $clientNameFull" . ($phone ? ", $phone." : ".");
        $language = $GLOBALS['SysValue']['other']['lang'] ?? ConcordPay::PAYMENT_PAGE_LANGUAGE;

        $urlConcordpay = $concordpay->urlSite() .
            '?payment=' . ConcordPay::CONCORDPAY_PAYMENT_CODE . '&order=' . $value['ouid'];
        $approve_url   = $urlConcordpay . '&result=approve';
        $decline_url   = $urlConcordpay . '&result=decline';
        $cancel_url    = $urlConcordpay . '&result=cancel';
        $callback_url  = $concordpay->urlSite() . '/phpshop/modules/concordpay/payment/result.php';

        if (empty($concordpay->option['status'])) {
            $concordpay->option['operation']    = 'Purchase';
            $concordpay->option['amount']       = $obj->get('total');
            $concordpay->option['order_id']     = 'order_' . $value['ouid'];
            $concordpay->option['currency_iso'] = $currencyISO;
            $concordpay->option['description']  = mb_convert_encoding($description, 'utf8', 'cp1251');
            $concordpay->option['approve_url']  = $approve_url;
            $concordpay->option['decline_url']  = $decline_url;
            $concordpay->option['cancel_url']   = $cancel_url;
            $concordpay->option['callback_url'] = $callback_url;
            $concordpay->option['language']     = $language;
            // Statistics.
            $concordpay->option['client_last_name']  = $clientNameSplit['client_last_name'];
            $concordpay->option['client_first_name'] = $clientNameSplit['client_first_name'];
            $concordpay->option['email'] = $email;
            $concordpay->option['phone'] = $phone;

            $concordpay->option['order_desc'] = 'Order ' . $value['ouid'];

            if (!$linkPayment = $concordpay->isLinkPayment()) {
                $payment_forma = $concordpay->getForm();
                $obj->set(
                    'payment_forma',
                    PHPShopText::form($payment_forma, 'pay', 'post', ConcordPay::FORM_ACTION)
                );
            }
            $form = ParseTemplateReturn(
                $GLOBALS['SysValue']['templates']['concordpay']['concordpay_payment_forma'],
                true
            );
        } else {
            $clean_cart = "
            <script>
                if (window.document.getElementById('num')) {
                    window.document.getElementById('num').innerHTML='0';
                    window.document.getElementById('sum').innerHTML='0';
                }
            </script>";
            $obj->set('mesageText', $concordpay->option['title_sub'] . $clean_cart);
            $form = ParseTemplateReturn($GLOBALS['SysValue']['templates']['order_forma_mesage']);

            unset($_SESSION['cart']);
        }
        $obj->set('orderMesage', $form);
    }
}

$addHandler = array('send_to_order' => 'send_concordpay_hook');
