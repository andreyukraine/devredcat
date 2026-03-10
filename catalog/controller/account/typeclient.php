<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountTypeClient extends Controller {
	private $error = array();

	public function index() {
		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$this->load->language('account/register');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$this->load->model('account/customer');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_register'),
			'href' => $this->url->link('account/register', '', true)
		);
		$data['text_account_already'] = sprintf($this->language->get('text_account_already'), $this->url->link('account/login', '', true));

    $this->load->model('account/customer_group');

    $group = $this->model_account_customer_group->getCustomerGroup($this->config->get('config_customer_group_id'));
    $data['type_default'] = $group['customer_group_id'];

    $group = $this->model_account_customer_group->getCustomerGroupByUid('2');
    $data['type_store'] = $group['customer_group_id'];

    $group = $this->model_account_customer_group->getCustomerGroupByUid('1');
    $data['type_breeder'] = $group['customer_group_id'];


		$data['register'] = $this->url->link('account/register', '', true);
    $data['back']     = $this->url->link('account/login', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/typeclient', $data));
	}

}
