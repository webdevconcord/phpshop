<?php

/**
 * Функция вывода истории платежей
 */
function actionStart()
{
    global $PHPShopInterface, $PHPShopModules, $TitlePage, $select_name;

    $PHPShopInterface->checkbox_action = false;
    $PHPShopInterface->setActionPanel($TitlePage, $select_name, false);
    $PHPShopInterface->setCaption(
        array(__('Функция'), '50%'),
        array(__('Номер заказа'), '10%'),
        array(__('Дата'), '10%'),
        array(__('Статус'), '20%')
    );

    $PHPShopOrm = new PHPShopOrm($PHPShopModules->getParam('base.concordpay.concordpay_log'));
    $PHPShopOrm->debug = false;

    $data = $PHPShopOrm->select(array('*'), $where = false, array('order' => 'id DESC'), array('limit' => 1000));

    if (is_array($data)) {
        foreach ($data as $row) {
            $PHPShopInterface->setRow(
                array(
                    'name' => $row['type'],
                    'link' => '?path=modules.dir.concordpay&id=' . $row['id']
                ),
                array(
                    'name' => $row['order_id'],
                    'link' => '?path=order&id=' . $row['order_id']
                ),
                PHPShopDate::get($row['date'], true),
                $row['status']
            );
        }
    }

    $PHPShopInterface->Compile();
}
