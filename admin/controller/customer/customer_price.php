<?php
class ControllerCustomerCustomerPrice extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('customer/customer_price');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('customer/customer_price');

		$this->getList();
	}

	public function add() {
		$this->load->language('customer/customer_price');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('customer/customer_price');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_customer_customer_price->addCustomerPrice($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('customer/customer_price', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('customer/customer_price');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('customer/customer_price');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_customer_customer_price->editCustomerPrice($this->request->get['customer_price_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('customer/customer_price', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('customer/customer_price');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('customer/customer_price');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $customer_price_id) {
				$this->model_customer_customer_price->deleteCustomerPrice($customer_price_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('customer/customer_price', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('customer/customer_price', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('customer/customer_price/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('customer/customer_price/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['customer_prices'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$customer_price_total = $this->model_customer_customer_price->getTotalCustomerPrices();

		$results = $this->model_customer_customer_price->getCustomerPrices($filter_data);

		foreach ($results as $result) {
			$data['customer_prices'][] = array(
				'customer_price_id' => $result['customer_price_id'],
				'name'              => $result['name'],
				'guid'              => $result['guid'],
				'edit'              => $this->url->link('customer/customer_price/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_price_id=' . $result['customer_price_id'] . $url, true)
			);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('customer/customer_price', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
		$data['sort_guid'] = $this->url->link('customer/customer_price', 'user_token=' . $this->session->data['user_token'] . '&sort=guid' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $customer_price_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('customer/customer_price', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($customer_price_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($customer_price_total - $this->config->get('config_limit_admin'))) ? $customer_price_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $customer_price_total, ceil($customer_price_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('customer/customer_price_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['customer_price_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['guid'])) {
			$data['error_guid'] = $this->error['guid'];
		} else {
			$data['error_guid'] = '';
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('customer/customer_price', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['customer_price_id'])) {
			$data['action'] = $this->url->link('customer/customer_price/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('customer/customer_price/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_price_id=' . $this->request->get['customer_price_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('customer/customer_price', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['customer_price_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$customer_price_info = $this->model_customer_customer_price->getCustomerPrice($this->request->get['customer_price_id']);
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($customer_price_info)) {
			$data['name'] = $customer_price_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['guid'])) {
			$data['guid'] = $this->request->post['guid'];
		} elseif (!empty($customer_price_info)) {
			$data['guid'] = $customer_price_info['guid'];
		} else {
			$data['guid'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('customer/customer_price_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'customer/customer_price')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 255)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (empty($this->request->post['guid'])) {
			$this->error['guid'] = $this->language->get('error_guid');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'customer/customer_price')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
