<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountLogout extends Controller {
	public function index() {
		if ($this->customer->isLogged()) {
			$this->customer->logout();

			unset($this->session->data['shipping_address']);
			unset($this->session->data['client_address_id']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_address']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);

      unset($this->session->data['last_sync_time']);

			$this->response->redirect($this->url->link('account/logout', '', true));
		}

		$this->load->language('account/logout');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_logout'),
			'href' => ""
		);

		$data['continue'] = $this->url->link('common/home');

    $data['text_message_title'] = $this->language->get('text_message_title');

    //режим каталогу
    if ($this->config->get('config_is_catalog')) {
      $data['config_is_catalog'] = $this->config->get('config_is_catalog');
    }

    $this->load->model('tool/image');
    $img_bg = "account_registr_bg.jpg";

    if (file_exists(DIR_IMAGE . $img_bg)) {
      $data["image"] = $this->model_tool_image->resize($img_bg, 1000, 1000);
    } else {
      $data["image"] = $this->model_tool_image->resize("no_image.png", 1000, 1000);
    }

		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/success', $data));
	}
}
