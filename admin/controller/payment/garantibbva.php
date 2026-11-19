<?php

namespace Opencart\Admin\Controller\Extension\Garantibbva\Payment;

ini_set("display_errors", "on");
error_reporting(E_ALL);


include_once DIR_EXTENSION . 'garantibbva/catalog/controller/vendor/include.php';

use Eticsoft\Sanalpospro\InternalApi;

class Garantibbva extends \Opencart\System\Engine\Controller
{
    public function install()
    {
        $this->load->model('setting/setting');
        $this->load->model('setting/extension');
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent([
            'code' => 'garantibbva_product_installment_tab',
            'description' => 'Ürün sayfasına taksit sekmesi ekler',
            'trigger' => 'catalog/view/product/product/after',
            'action' => 'extension/garantibbva/payment/garantibbva.addProductTab',
            'status' => 1,
            'sort_order' => 10
        ]);
        $this->model_setting_event->addEvent([
            'code' => 'garantibbva_admin_order',
            'description' => 'Sipariş detay sayfasına ödeme bilgileri sekmesi ekler',
            'trigger' => 'admin/view/sale/order_info/before',
            'action' => 'extension/garantibbva/payment/garantibbva.addOrderInfo',
            'status' => 1,
            'sort_order' => 10
        ]);
        $xfvv = hash('sha256', time() . rand(1000000, 9999999));
        $this->model_setting_setting->editSetting('payment_garantibbva', [
            'payment_garantibbva_order_status' => 2,
            'payment_garantibbva_currency_convert' => 'no',
            'payment_garantibbva_showInstallmentsTabs' => 'no',
            'payment_garantibbva_paymentPageTheme' => 'classic',
            'payment_garantibbva_installments' => '{}',
            'payment_garantibbva_status' => 1,
            'payment_garantibbva_public_key' => '',
            'payment_garantibbva_secret_key' => '',
            'payment_garantibbva_xfvv' => $xfvv,
        ]);
        $this->model_setting_extension->install('payment', 'garantibbva', 'garantibbva');
    }
    public function uninstall()
    {
        $this->load->model('setting/setting');
        $this->load->model('setting/extension');
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('garantibbva_product_installment_tab');
        $this->model_setting_event->deleteEventByCode('garantibbva_admin_order');
        $this->model_setting_setting->deleteSetting('payment_garantibbva');
        $this->model_setting_extension->uninstall('payment', 'garantibbva');
    }
    public function index()
    {
        $this->load->language('extension/garantibbva/payment/garantibbva');

        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $this->load->model('localisation/order_status');

        $php_version = phpversion();

        $data['heading_title'] = $this->language->get('heading_title');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['header'] = $this->load->controller('common/header');

        if (version_compare($php_version, '7.4', '<')) {
            $data['php_version_warning'] = $this->language->get('php_version_warning');
            $this->editSetting('payment_garantibbva_status', 0);
            return $this->response->setOutput($this->load->view('extension/garantibbva/payment/garantibbva', $data));
        }

        $data['php_version_warning'] = '';

        $order_a = $this->model_localisation_order_status->getOrderStatuses();
        $order_statuses = [];
        foreach ($order_a as $order_status) {
            $order_statuses[$order_status['order_status_id']] = $order_status['name'];
        }
        $settings = $this->model_setting_setting->getSetting('payment_garantibbva');

        if ($settings['payment_garantibbva_status'] == 0) {
            $this->editSetting('payment_garantibbva_status', 1);
        }
        
        $data['order_status'] = $settings['payment_garantibbva_order_status'];
        $data['currency_convert'] =   $settings['payment_garantibbva_currency_convert'];
        $data['showInstallmentsTabs'] = $settings['payment_garantibbva_showInstallmentsTabs'];
        $data['paymentPageTheme'] = $settings['payment_garantibbva_paymentPageTheme'];
        $data['order_statuses'] = json_encode($order_statuses);

        $data['text_yes'] = $this->language->get('yes');
        $data['text_no'] = $this->language->get('no');
        $data['text_classic'] = $this->language->get('classic');
        $data['text_modern'] = $this->language->get('modern');

        $data['iapi_url'] = $this->url->link('extension/garantibbva/payment/garantibbva.iapi', 'user_token=' . $this->session->data['user_token']);
        $data['iapi_xfvv'] = $settings['payment_garantibbva_xfvv'];
        $data['store_url'] = HTTP_CATALOG;
        return $this->response->setOutput($this->load->view('extension/garantibbva/payment/garantibbva', $data));
    }

    private function editSetting($key, $value)
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('payment_garantibbva');
        $settings[$key] = $value;
        $this->model_setting_setting->editSetting('payment_garantibbva', $settings);
    }


    public function iapi()
    {
        if (!isset($_SERVER['HTTP_REFERER']) || parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
            header('Content-Type: application/json');
            header('HTTP/1.0 404 Not Found');
            die(json_encode(['status' => 'error', 'message' => 'Not Found']));
        }
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting;
        $api = InternalApi::getInstance()->setController($this)->setSettings($settings)->run();
        header('Content-Type: application/json');
        die(json_encode($api->response));
    }

    public function addOrderInfo(&$route, &$data, &$output)
    {
        $this->load->language('extension/garantibbva/payment/garantibbva');
        $order_id = $this->request->get['order_id'];
        $this->load->model('sale/order');
        $order = $this->model_sale_order->getOrder($order_id);
        if (empty($order) || $order['payment_method']['code'] != 'garantibbva.garantibbva' || $order['payment_method']['code'] != 'garantibbva') {
            return;
        }

        $order_history = $this->model_sale_order->getHistories($order_id);
        $last_history = [];
        if (!empty($order_history)) {
            $last_history = $order_history[0];
        }
        if (empty($last_history) || !strpos($last_history['comment'], 'Garanti BBVA')) {
            return;
        }
        $transaction_id = '';
        if (!empty($last_history['comment'])) {
            $comment_parts = explode(' ', trim($last_history['comment']));
            $transaction_id = end($comment_parts);
        }
        try {
            if (empty($transaction_id)) {
                return;
            }
            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting;
            \Eticsoft\Sanalpospro\EticConfig::setSettings($settings);
            $payment_info = \Eticsoft\Sanalpospro\Payment::getByToken($transaction_id);
            if ($payment_info['status'] == 'success') {
                $transaction_data = $payment_info['data']['transaction'];
                $order_data['order_status'] = $this->language->get('text_payment_' . $transaction_data['status']);
                $order_data['transaction'] = $transaction_data;
                $order_data['logo'] = (($payment_info['data']['program'])['theme'])['logo_url'];
                if ($transaction_data['gateway_fee'] > 0) {
                    $order_data['process_fee'] = $transaction_data['gateway_fee'];
                    $order_data['text_process_fee'] = $this->language->get('text_process_fee');
                }
            }
        } catch (\Exception $e) {
            $order_data['error'] = $e->getMessage();
        }
        $order_data['warning_message'] = $this->language->get('text_warning_message');
        $order_data['process_detail'] = $this->language->get('text_process_detail');
        $order_data['process_value'] = $this->language->get('text_process_value');
        $order_data['text_process_detail'] = $this->language->get('text_process_detail');
        $order_data['text_process_value'] = $this->language->get('text_process_value');
        $order_data['text_order_status'] = $this->language->get('text_order_status');
        $order_data['text_order_id'] = $this->language->get('text_order_id');
        $order_data['text_amount'] = $this->language->get('text_amount');
        $order_data['text_currency'] = $this->language->get('text_currency');
        $order_data['text_installment'] = $this->language->get('text_installment');
        $order_data['text_process_fee'] = $this->language->get('text_process_fee');
        $order_data['text_no_installment'] = $this->language->get('text_no_installment');
        $order_data['text_created_at'] = $this->language->get('text_created_at');
        $content = $this->load->view('extension/garantibbva/payment/garantibbva/order/order_info', $order_data);
        $data['tabs'][] = [
            'code' => 'garantibbva_order_info',
            'title' => 'Garanti BBVA',
            'content' => $content
        ];
    }
}
