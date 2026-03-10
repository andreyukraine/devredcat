<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountLogin extends Controller {
	private $error = array();

	public function index() {
		$this->load->model('account/customer');

		// Login override for admin users
		if (!empty($this->request->get['token'])) {
			$this->customer->logout();
			$this->cart->clear();

			unset($this->session->data['order_id']);
			unset($this->session->data['payment_address']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['shipping_address']);
			unset($this->session->data['client_address_id']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['comment']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);

			$customer_info = $this->model_account_customer->getCustomerByToken($this->request->get['token']);

			if ($customer_info && $this->customer->login($customer_info['email'], '', true)) {
				// Default Addresses
				$this->load->model('account/address');

				if ($this->config->get('config_tax_customer') == 'payment') {
					$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				}

				if ($this->config->get('config_tax_customer') == 'shipping') {
					$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				}

				$this->response->redirect($this->url->link('account/account', '', true));
			}
		}

    if ($this->customer->isLogged()) {
      $this->response->redirect($this->url->link('account/account', '', true));
    }

		$this->load->language('account/login');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			// Unset guest
			unset($this->session->data['guest']);

			// Default Shipping Address
			$this->load->model('account/address');

			if ($this->config->get('config_tax_customer') == 'payment') {
				$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			}

			if ($this->config->get('config_tax_customer') == 'shipping') {
				$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			}

			// Wishlist
			if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
				$this->load->model('account/wishlist');

				foreach ($this->session->data['wishlist'] as $key => $product_id) {
					$this->model_account_wishlist->addWishlist($product_id);

					unset($this->session->data['wishlist'][$key]);
				}
			}

      if (isset($this->session->data['login_in_cart'])){
        unset($this->session->data['login_in_cart']);
        $this->response->redirect($this->url->link('checkout/checkout', '', true));
      }

			// Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
			if (isset($this->request->post['redirect']) && $this->request->post['redirect'] != $this->url->link('account/logout', '', true) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false || strpos($this->request->post['redirect'], $this->config->get('config_ssl')) !== false)) {
				$this->response->redirect(str_replace('&amp;', '&', $this->request->post['redirect']));
			} else {
				$this->response->redirect($this->url->link('account/account', '', true));
			}
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_login'),
			'href' => $this->url->link('account/login', '', true)
		);

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		} elseif (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

    if (isset($this->error['error_email'])) {
      $data['error_email'] = $this->error['error_email'];
    }else{
      $data['error_email'] = '';
    }

    if (isset($this->error['error_password'])) {
      $data['error_password'] = $this->error['error_password'];
    }else{
      $data['error_password'] = '';
    }

		$data['action'] = $this->url->link('account/login', '', true);

		$data['register'] = $this->url->link('account/typeclient', '', true);

		$data['forgotten'] = $this->url->link('account/forgotten', '', true);

		// Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
		if (isset($this->request->post['redirect']) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false || strpos($this->request->post['redirect'], $this->config->get('config_ssl')) !== false)) {
			$data['redirect'] = $this->request->post['redirect'];
		} elseif (isset($this->session->data['redirect'])) {
			$data['redirect'] = $this->session->data['redirect'];

			unset($this->session->data['redirect']);
		} else {
			$data['redirect'] = '';
		}

    if (isset($this->request->get['cart'])){
      $this->session->data['login_in_cart'] = true;
    }

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

    //режим каталогу
    if ($this->config->get('config_is_catalog')) {
      $data['config_is_catalog'] = $this->config->get('config_is_catalog');
    }

    $this->load->model('tool/image');
    $img_bg = "account_login_bg.jpg";

    if (file_exists(DIR_IMAGE . $img_bg)) {
      $data["image"] = $this->model_tool_image->resize($img_bg, 1000, 1000);
    } else {
      $data["image"] = $this->model_tool_image->resize("no_image.png", 1000, 1000);
    }

    $data['heading_title'] = $this->language->get('text_login');
    $data['text_register'] = $this->language->get('text_register');

		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/login', $data));
	}

  protected function validate() {

    $email = trim($this->request->post['email']);
    $pass = trim($this->request->post['password']);

    if (!empty($email)) {
      // Check how many login attempts have been made.
      $login_info = $this->model_account_customer->getLoginAttempts($email);
      if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
        $this->error['warning'] = $this->language->get('error_attempts');
      }

      // Check if customer has been approved.
      $customer_info = $this->model_account_customer->getCustomerByEmail($email);

      if ($customer_info && !$customer_info['status']) {
        $this->error['warning'] = $this->language->get('error_approved');
      }
    }else{
      $this->error['error_email'] = $this->language->get('error_email');
    }

    if (empty($pass)) {
      $this->error['error_password'] = $this->language->get('error_password');
    }

    if (!$this->error) {
      if (!$this->customer->login($email, $pass)){
        $this->error['warning'] = $this->language->get('error_login');

        $this->model_account_customer->addLoginAttempt($email);
      } else {
        $this->model_account_customer->deleteLoginAttempts($email);
      }
    }

    return !$this->error;
  }
}
