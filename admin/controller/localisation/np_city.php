<?php
class ControllerLocalisationNpCity extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('localisation/np_city');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/np_city');

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'nc.name';
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
			'href' => $this->url->link('localisation/np_city', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['import'] = $this->url->link('localisation/zone/import', 'user_token=' . $this->session->data['user_token'], true);
		$data['button_import'] = $this->language->get('button_import');

		$data['cities'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$city_total = $this->model_localisation_np_city->getTotalCities();

		$results = $this->model_localisation_np_city->getCities($filter_data);

		foreach ($results as $result) {
			$data['cities'][] = array(
				'city_id'     => $result['city_id'],
				'np_city_id'  => $result['np_city_id'],
				'name'        => $result['name'],
				'zone'        => $result['zone'],
				'type'        => $result['settlement_type']
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

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

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('localisation/np_city', 'user_token=' . $this->session->data['user_token'] . '&sort=nc.name' . $url, true);
		$data['sort_zone'] = $this->url->link('localisation/np_city', 'user_token=' . $this->session->data['user_token'] . '&sort=z.name' . $url, true);
		$data['sort_type'] = $this->url->link('localisation/np_city', 'user_token=' . $this->session->data['user_token'] . '&sort=nc.settlement_type' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $city_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('localisation/np_city', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($city_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($city_total - $this->config->get('config_limit_admin'))) ? $city_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $city_total, ceil($city_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('localisation/np_city_list', $data));
	}
}
