<?php

namespace Eticsoft\Sanalpospro;

use Opencart\System\Library\Request;

class EticTools
{

    private static $controller;

    public static function setController($controller)
    {
        self::$controller = $controller;
    }

    /**
     * OpenCart 4 için POST değerini alma
     */
    public static function postVal($key, $default = null)
    {
        $request = new Request();
        if (isset($request->post[$key])) {
            return $request->post[$key];
        }
        return $default;
    }

    /**
     * OpenCart 4 için GET değerini alma
     */
    public static function getVal($key, $default = null)
    {
        $request = new Request();
        if (isset($request->get[$key])) {
            return $request->get[$key];
        }
        return $default;
    }

    public static function getSessionData($key)
    {
        return !empty(self::$controller->session->data[$key]) ? self::$controller->session->data[$key] : [];
    }

    public static function getSession()
    {
        return self::$controller->session->data;
    }


    public static function getOrderId()
    {
        return self::getSessionData('order_id');
    }

    public static function getOrder()
    {
        return self::getOrderInstance()->getOrder(self::getOrderId());
    }

    public static function getOrderInstance()
    {
        self::$controller->load->model('checkout/order');
        return self::$controller->model_checkout_order;
    }

    public static function getCart()
    {
        self::$controller->load->model('checkout/cart');
        return self::$controller->model_checkout_cart;
    }

    public static function getCartItems()
    {
        return self::$controller->model_checkout_cart->getProducts();
    }

    public static function getCartTotals()
    {
        $cart = self::getCart();
        $totals = [];
        $taxes = self::$controller->cart->getTaxes();
        $total = 0;
        ($cart->getTotals)($totals, $taxes, $total);
        $coupon = 0;
        $cleared_taxes = [];
        foreach ($totals as $item) {
            if ($item['code'] === 'coupon') {
                $coupon = $item['value'];
            }
            if ($item['code'] === 'tax') {
                $cleared_taxes[] = $item;
            }
        }
        $res = [
            'total' => $total,
            'totals' => $totals,
            'taxes' => $cleared_taxes,
            'coupon' => $coupon
        ];
        return $res;
    }

    public static function getCurrency()
    {
        return self::$controller->session->data['currency'] ?? self::$controller->config->get('config_currency');
    }

    public static function getAmountCurrencyFormated($amount)
    {
        return self::$controller->currency->format($amount, self::getCurrency(), false, false);
    }

    public static function getShippingCost()
    {
        $tax = isset(self::$controller->session->data['shipping_method']) ? self::$controller->session->data['shipping_method']['cost'] : 0;
        return self::getAmountCurrencyFormated($tax);
    }

    public static function calculateWithTaxAmount($amount, $tax_class_id)
    {
        return self::$controller->tax->calculate($amount, $tax_class_id, self::$controller->config->get('config_tax'));
    }

    public static function redirectToCart()
    {
        self::$controller->response->redirect(self::$controller->url->link('checkout/cart'));
    }

    public static function addCommissionFeeToTotal($order_id, $amount)
    {
        self::$controller->load->language('payment/garantibbva');
        $title = self::$controller->language->get('commission_fee');
        self::$controller->db->query("INSERT INTO `" . DB_PREFIX . "order_total` SET order_id = '" . (int)$order_id . "', code = 'fee', title = '" . $title . "', value = '" . (float)$amount . "', sort_order = '4'");
        self::$controller->db->query("UPDATE `" . DB_PREFIX . "order_total` SET value = value + '" . (float)$amount . "' WHERE order_id = '" . (int)$order_id . "' AND code = 'total'");
        self::$controller->db->query("UPDATE `" . DB_PREFIX . "order` SET total = total + '" . (float)$amount . "' WHERE order_id = '" . (int)$order_id . "'");
    }

    public static function addOrderHistory($payment_token)
    {
        self::$controller->load->model('checkout/order');
        self::$controller->load->language('extension/garantibbva/payment/garantibbva');
        self::$controller->model_checkout_order->addHistory(
            self::getOrderId(),
            !empty(self::$controller->config->get('payment_garantibbva_order_status')) ? self::$controller->config->get('payment_garantibbva_order_status') : 2,
            self::$controller->language->get('payment_success') . ' Garanti BBVA ' . $payment_token
        );
    }

    public static function getLink($route)
    {
        return self::$controller->url->link($route);
    }

    public static function getConfigData($key)
    {
        return self::$controller->config->get($key);
    }

    public static function getCountryInfo($country_id) {
        self::$controller->load->model('localisation/country');
        return self::$controller->model_localisation_country->getCountry($country_id);
    }

    public static function getCurrencyInfo($currency) {  
        self::$controller->load->model('localisation/currency');
        return self::$controller->model_localisation_currency->getCurrencyByCode($currency);
    }
}
