<?php
class ControllerExtensionShippingNp extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/shipping/np');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_np', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/np', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/shipping/np', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

    if (isset($this->request->post['shipping_np_key'])) {
      $data['shipping_np_key'] = $this->request->post['shipping_np_key'];
    } else {
      $data['shipping_np_key'] = $this->config->get('shipping_np_key');
    }

		if (isset($this->request->post['shipping_np_status'])) {
			$data['shipping_np_status'] = $this->request->post['shipping_np_status'];
		} else {
			$data['shipping_np_status'] = $this->config->get('shipping_np_status');
		}

		if (isset($this->request->post['shipping_np_sort_order'])) {
			$data['shipping_np_sort_order'] = $this->request->post['shipping_np_sort_order'];
		} else {
			$data['shipping_np_sort_order'] = $this->config->get('shipping_np_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/np', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/np')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
