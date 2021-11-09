<?php

PHPShopObj::loadClass('order');
// SQL
$PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['concordpay']['concordpay_system']);

// Функция обновления
function actionUpdate()
{
    global $PHPShopOrm, $PHPShopModules;

    // Настройки витрины
    $PHPShopModules->updateOption($_GET['id'], $_POST['servers']);
    $PHPShopOrm->debug = false;
    $action = $PHPShopOrm->update($_POST);
    header('Location: ?path=modules&id=' . $_GET['id']);

    return $action;
}

// Обновление версии модуля
function actionBaseUpdate()
{
    global $PHPShopModules, $PHPShopOrm;

    $PHPShopOrm->clean();
    $option = $PHPShopOrm->select();
    $new_version = $PHPShopModules->getUpdate($option['version']);
    $PHPShopOrm->clean();
    $action = $PHPShopOrm->update(array('version_new' => $new_version));

    return $action;
}

/**
 * @return bool
 */
function actionStart()
{
    global $PHPShopGUI, $PHPShopOrm;

    // Выборка
    $data = $PHPShopOrm->select();

    $Tab1 = '';
    $Tab1 .= $PHPShopGUI->setField(
        __('Идентификатор продавца'),
        $PHPShopGUI->setInputText(false, 'merchant_id_new', $data['merchant_id'], 300)
    );
    $Tab1 .= $PHPShopGUI->setField(
        __('Секретный ключ'),
        $PHPShopGUI->setInputText(false, 'password_new', $data['password'], 300)
    );

    // Статус заказа
    $PHPShopOrderStatusArray = new PHPShopOrderStatusArray();
    $OrderStatusArray = $PHPShopOrderStatusArray->getArray();
    $order_status_value[] = array(__('Новый заказ'), 0, $data['status_checkout']);
    if (is_array($OrderStatusArray)) {
        foreach ($OrderStatusArray as $order_status) {
            $order_status_value[] = array($order_status['name'], $order_status['id'], $data['status_checkout']);
        }
    }

    $Tab1 .= $PHPShopGUI->setField(
        __('Статус заказа до оплаты'),
        $PHPShopGUI->setSelect('status_checkout_new', $order_status_value, 300),
        '',
        '',
        'status-checkout'
    );

    $Tab1 .= $PHPShopGUI->setField(
        __('Сообщение предварительной проверки'),
        $PHPShopGUI->setTextarea('title_sub_new', $data['title_sub'])
    );

    $Tab1 .= $PHPShopGUI->setField(
        __('Описание оплаты'),
        $PHPShopGUI->setTextarea('title_payment_new', $data['title_payment'])
    );

    $info = '
        <h4>Настройка модуля</h4>
        <ol>
          <li>Зарегистрируйтесь на <a href="https://pay.concord.ua">сервисном портале</a>.</li>
          <li>Подпишите договор на интернет-эквайринг.</li>
          <li>В настройках модуля введите "Идентификатор продавца" и "Секретный ключ" из личного кабинета ConcordPay.</li>
        </ol>';

    $Tab3 = $PHPShopGUI->setPay(null, false, $data['version'], false);

    $PHPShopGUI->setTab(
        array(__('Настройки'), $Tab1, true),
        array(__('Инструкция'), $PHPShopGUI->setInfo($info)),
        array(__('О Модуле'), $Tab3)
    );

    $ContentFooter = $PHPShopGUI->setInput(
        'submit',
        'saveID',
        __('Сохранить'),
        'right',
        80,
        '',
        'but',
        'actionUpdate.modules.edit'
    );

    $PHPShopGUI->setFooter($ContentFooter);

    return true;
}

$PHPShopGUI->getAction();
$PHPShopGUI->setLoader($_POST['editID'], 'actionStart');
