<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountForgotten extends Controller {
	private $error = array();

	public function index() {
		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$this->load->language('account/forgotten');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$this->load->model('account/customer');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_account_customer->editCode($this->request->post['email'], token(40));

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_forgotten'),
			'href' => $this->url->link('account/forgotten', '', true)
		);

    if (isset($this->request->post['email'])) {
      $data['error_email'] = $this->error['email'];
    } else {
      $data['error_email'] = '';
    }

    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    if (isset($this->error['captcha'])) {
      $data['error_captcha'] = $this->error['captcha'];
    } else {
      $data['error_captcha'] = '';
    }

		$data['action'] = $this->url->link('account/forgotten', '', true);

		$data['back'] = $this->url->link('account/login', '', true);

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = '';
		}

    $this->load->model('tool/image');
    $img_bg = "account_login_bg.jpg";

    if (file_exists(DIR_IMAGE . $img_bg)) {
      $data["image"] = $this->model_tool_image->resize($img_bg, 1000, 1000);
    } else {
      $data["image"] = $this->model_tool_image->resize("no_image.png", 1000, 1000);
    }

    $data['login'] = $this->url->link('account/login', '', true);

    //режим каталогу
    if ($this->config->get('config_is_catalog')) {
      $data['config_is_catalog'] = $this->config->get('config_is_catalog');
    }

    $data['text_login'] = $this->language->get('text_login');
    $data['text_forgotten'] = $this->language->get('text_forgotten');

    // Captcha
    if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
      $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
    } else {
      $data['captcha'] = '';
    }


		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/forgotten', $data));
	}

	protected function validate() {
    if (!isset($this->request->post['email'])) {
      $this->error['email'] = $this->language->get('error_email');
    } elseif (!$this->model_account_customer->getTotalCustomersByEmail($this->request->post['email'])) {
      $this->error['email'] = $this->language->get('error_email');
    }

    // Check if customer has been approved.
    $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);
    if ($customer_info && !$customer_info['status']) {
      $this->error['email'] = $this->language->get('error_approved');
    }

    // Captcha
    if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
      $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

      if ($captcha) {
        $this->error['captcha'] = $captcha;
      }
    }

		return !$this->error;
	}
}
