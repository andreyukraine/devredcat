<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountMenu extends Controller {
	private $error = array();

	public function index() {

		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/password', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$data['back'] = $this->url->link('account/account', '', true);

    $data['is_mobile'] = $this->mobile_detect->isMobile();

    //----------------- copy all --------------------------//

    $this->load->language('account/account');
    $data['edit'] = $this->url->link('account/edit', '', true);
    $data['password'] = $this->url->link('account/password', '', true);
    $data['address'] = $this->url->link('account/address', '', true);

    $this->load->model('account/order');

    $data['order_total'] = $this->model_account_order->getTotalOrders();

    $data['credit_cards'] = array();

    $files = glob(DIR_APPLICATION . 'controller/extension/credit_card/*.php');

    foreach ($files as $file) {
      $code = basename($file, '.php');

      if ($this->config->get('payment_' . $code . '_status') && $this->config->get('payment_' . $code . '_card')) {
        $this->load->language('extension/credit_card/' . $code, 'extension');

        $data['credit_cards'][] = array(
          'name' => $this->language->get('extension')->get('heading_title'),
          'href' => $this->url->link('extension/credit_card/' . $code, '', true)
        );
      }
    }

    $data['route'] = "account/account";

    if (isset($this->request->get['route'])) {
      $data['route'] = $this->request->get['route'];
    }

    $data['wishlist'] = $this->url->link('account/wishlist');
    $data['card'] = $this->url->link('account/card');
    $data['address'] = $this->url->link('account/address');

    if ($this->customer->getGroupId() != 21) {
      $data['delivery'] = $this->url->link('account/delivery');
    }

    $data['order'] = $this->url->link('account/order', '', true);
    $data['download'] = $this->url->link('account/download', '', true);

    if ($this->config->get('total_reward_status')) {
      $data['reward'] = $this->url->link('account/reward', '', true);
    } else {
      $data['reward'] = '';
    }

    $data['return'] = $this->url->link('account/return', '', true);
    $data['transaction'] = $this->url->link('account/transaction', '', true);
    $data['newsletter'] = $this->url->link('account/newsletter', '', true);
    $data['recurring'] = $this->url->link('account/recurring', '', true);

    $data['text_edit'] = $this->language->get('text_edit');
    $data['text_password'] = $this->language->get('text_password');
    $data['text_wishlist'] = $this->language->get('text_wishlist');

    $data['text_address'] = $this->language->get('text_address');


    $data['logout'] = $this->url->link('account/logout', '', true);
    $data['text_logout'] = $this->language->get('text_logout');
    //----------------- end copy all --------------------------//

    return $this->load->view('account/menu', $data);

	}

}
