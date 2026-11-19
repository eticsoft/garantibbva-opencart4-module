<?php

namespace Opencart\Catalog\Controller\Extension\Garantibbva\Api;

include_once DIR_EXTENSION . 'garantibbva/catalog/controller/vendor/include.php';

use Eticsoft\Sanalpospro\InternalApi;

class Garantibbvaiapi extends \Opencart\System\Engine\Controller {
    public function index() {
        if (!isset($_SERVER['HTTP_REFERER']) || parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
            header('Content-Type: application/json');
            header('HTTP/1.0 403 Forbidden'); 
            die(json_encode(['status' => 'error', 'message' => 'Access denied']));
        }
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting;
        $api = InternalApi::getInstance()->setSettings($settings)->setController($this)->run();
        header('Content-Type: application/json');
        die(json_encode($api->response));    
    }
}
