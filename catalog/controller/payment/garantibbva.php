<?php

namespace Opencart\Catalog\Controller\Extension\Garantibbva\Payment;

class Garantibbva extends \Opencart\System\Engine\Controller
{
    public function index()
    {
        $this->load->language('extension/garantibbva/payment/garantibbva');

        $data['text_instruction'] = $this->language->get('text_instruction');
        $data['text_description'] = $this->language->get('text_description');
        $data['text_payment'] = $this->language->get('text_payment');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['text_payment_success'] = $this->language->get('payment_success');
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['payment_url'] = $this->url->link('extension/garantibbva/api/garantibbvaiapi');
        $data['order_confirmation_url'] = $this->url->link('extension/garantibbva/api/garantibbvaiapi');
        $data['xfvv'] = $this->config->get('payment_garantibbva_xfvv');
        return $this->load->view('extension/garantibbva/payment/garantibbva', $data);
    }

    public function addProductTab(&$route, &$data, &$output)
    {
        if ($this->config->get('payment_garantibbva_showInstallmentsTabs') != 'yes') {
            return;
        }
        $this->load->model('setting/setting');
        $this->load->language('extension/garantibbva/payment/garantibbva');
        $title = $this->language->get('installments_title');
        $installments = json_decode($this->model_setting_setting->getSetting('payment_garantibbva')['payment_garantibbva_installments'], true);
        if (empty($installments)) {
            $installments = [];
        }

        foreach ($installments as $key => $installment) {
            // Her bir installment içerisindeki tüm gateway'lerin "off" olup olmadığını kontrol et
            $allGatewaysOff = true;

            foreach ($installment as $monthData) {
                if ($monthData['gateway'] != 'off') {
                    $allGatewaysOff = false;
                    break; // Bir tane bile "off" olmayan gateway varsa, kontrolü bırak
                }
            }

            // Eğer tüm gateway'ler "off" ise, bu installment'ı kaldır
            if ($allGatewaysOff) {
                unset($installments[$key]);
            }
            if ($key == 'default') {
                unset($installments[$key]);
            }
        }

        $data['text_monthly_payment'] = $this->language->get('monthly_payment');
        $data['text_total'] = $this->language->get('total');
        $data['text_installment_count'] = $this->language->get('installment_count');
        $data['text_installment'] = $this->language->get('installment');
        $data['text_note'] = $this->language->get('text_note');

        $product = $this->model_catalog_product->getProduct($this->request->get['product_id']);
        $currency = $this->session->data['currency'];
        $prc = $product['special'] ? $this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')) : $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));        
        $price = $this->currency->format($prc, $currency, false, false);


        $data['installments'] = $installments;

        $currencies = array(
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£'
        );

        $data['currency'] = isset($currencies[$currency]) ? $currencies[$currency] : $currency;
        $data['garantibbva_price'] = (float)$price;
        // Yeni tab HTML içeriği
        $theme = $this->config->get('payment_garantibbva_paymentPageTheme');
        if ($theme == 'modern') {
            $custom_tab = $this->load->view('extension/garantibbva/payment/garantibbva/installments/modern', $data);
        } else {
            $custom_tab = $this->load->view('extension/garantibbva/payment/garantibbva/installments/classic', $data);
        }
                
        //default theme için
        // Tab başlığı HTML içeriği
        $custom_tab_header =
            '<li class="nav-item" role="presentation">
            <a class="nav-link" href="#tab-garantibbva-installments" data-bs-toggle="tab" aria-selected="true" role="tab">' . $title . '</a>
        </li>';


        $output = preg_replace(
            '/<ul class="nav nav-tabs">(.*?)<\/ul>/s',
            '<ul class="nav nav-tabs">$1' . $custom_tab_header . '</ul>',
            $output
        );

        $output = str_replace(
            '<div class="tab-content">',
            '<div class="tab-content">' . $custom_tab,
            $output
        );
    }
}
