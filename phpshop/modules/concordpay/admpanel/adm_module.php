<?php

PHPShopObj::loadClass('order');
// SQL
$PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['concordpay']['concordpay_system']);

// ������� ����������
function actionUpdate()
{
    global $PHPShopOrm, $PHPShopModules;

    // ��������� �������
    $PHPShopModules->updateOption($_GET['id'], $_POST['servers']);
    $PHPShopOrm->debug = false;
    $action = $PHPShopOrm->update($_POST);
    header('Location: ?path=modules&id=' . $_GET['id']);

    return $action;
}

// ���������� ������ ������
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

    // �������
    $data = $PHPShopOrm->select();

    $Tab1 = '';
    $Tab1 .= $PHPShopGUI->setField(
        __('������������� ��������'),
        $PHPShopGUI->setInputText(false, 'merchant_id_new', $data['merchant_id'], 300)
    );
    $Tab1 .= $PHPShopGUI->setField(
        __('��������� ����'),
        $PHPShopGUI->setInputText(false, 'password_new', $data['password'], 300)
    );

    // ������ ������
    $PHPShopOrderStatusArray = new PHPShopOrderStatusArray();
    $OrderStatusArray = $PHPShopOrderStatusArray->getArray();
    $order_status_value[] = array(__('����� �����'), 0, $data['status_checkout']);
    if (is_array($OrderStatusArray)) {
        foreach ($OrderStatusArray as $order_status) {
            $order_status_value[] = array($order_status['name'], $order_status['id'], $data['status_checkout']);
        }
    }

    $Tab1 .= $PHPShopGUI->setField(
        __('������ ������ �� ������'),
        $PHPShopGUI->setSelect('status_checkout_new', $order_status_value, 300),
        '',
        '',
        'status-checkout'
    );

    $Tab1 .= $PHPShopGUI->setField(
        __('��������� ��������������� ��������'),
        $PHPShopGUI->setTextarea('title_sub_new', $data['title_sub'])
    );

    $Tab1 .= $PHPShopGUI->setField(
        __('�������� ������'),
        $PHPShopGUI->setTextarea('title_payment_new', $data['title_payment'])
    );

    $info = '
        <h4>��������� ������</h4>
        <ol>
          <li>����������������� �� <a href="https://pay.concord.ua">��������� �������</a>.</li>
          <li>��������� ������� �� ��������-���������.</li>
          <li>� ���������� ������ ������� "������������� ��������" � "��������� ����" �� ������� �������� ConcordPay.</li>
        </ol>';

    $Tab3 = $PHPShopGUI->setPay(null, false, $data['version'], false);

    $PHPShopGUI->setTab(
        array(__('���������'), $Tab1, true),
        array(__('����������'), $PHPShopGUI->setInfo($info)),
        array(__('� ������'), $Tab3)
    );

    $ContentFooter = $PHPShopGUI->setInput(
        'submit',
        'saveID',
        __('���������'),
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
