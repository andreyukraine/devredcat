<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountCard extends Controller {
	private $error = array();

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/card', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/card');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$this->load->model('account/card');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_account_card->editCustomer($this->customer->getId(), $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('info_card'),
			'href' => $this->url->link('account/card', '', true)
		);

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['telephone'])) {
			$data['error_telephone'] = $this->error['telephone'];
		} else {
			$data['error_telephone'] = '';
		}

		$data['action'] = $this->url->link('account/card', '', true);

		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			$customer_info = $this->model_account_card->getCustomer($this->customer->getId());
		}

    $data['telephone'] = $customer_info['telephone'];

    $data['card_number'] = "00000000000";
    $data['card_code'] = false;
    $data['sum'] = 0;
    $data['percent'] = 0;

    if (!empty($customer_info['telephone'])){
      $this->load->model('extension/module/occards');
      $this->load->model('extension/module/discount');

      $card = $this->model_extension_module_occards->getCardCustomer($this->customer->getId());
      if ($card){
        $data['card_number'] = $card['card_number'];
        $data['card_code'] = $card['card_code'];
        $data['sum'] = $card['sum'];
        $data['percent'] = (int)$this->model_extension_module_discount->getLoyaltyDiscount()['discount'];
      }
    }



		$data['back'] = $this->url->link('account/account', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/card', $data));
	}

	protected function validate() {
		if ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32)) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}
		return !$this->error;
	}
}
