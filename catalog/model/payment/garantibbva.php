<?php
namespace Opencart\Catalog\Model\Extension\Garantibbva\Payment;

class Garantibbva extends \Opencart\System\Engine\Model
{
	public function getMethods()
	{
		$this->load->language('extension/garantibbva/payment/garantibbva');

		$method_data = array(
			'code'       => 'garantibbva',
			'name'      => $this->language->get('text_title'),
			'option'      =>  [
				'garantibbva' => [
					'code' => 'garantibbva.garantibbva',
					'name' => $this->language->get('text_title')
				]
			],
			'sort_order' => 1
		);
		return $method_data;
	}

	public function getMethod()
	{
		$this->load->language('extension/garantibbva/payment/garantibbva');

		$method_data = [
			'code'       => 'garantibbva',
			'title'      => $this->language->get('heading_title'),
			'sort_order' => 1
		];

		return $method_data;
	}
}
