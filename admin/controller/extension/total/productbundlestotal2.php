<?php
class ControllerExtensionTotalProductBundlesTotal2 extends Controller {
    private $moduleName;
    private $modulePath;
    private $extensionsLink;
    private $error = array(); 
    private $data = array();

    public function __construct($registry) {
        parent::__construct($registry);
        
        // Config Loader
        $this->config->load('isenselabs/productbundles');
        
        /* Fill Main Variables - Begin */
        $this->moduleName       = $this->config->get('productbundlestotal2_name');
        $this->modulePath       = $this->config->get('productbundlestotal2_path');
        $this->extensionsLink   = $this->url->link($this->config->get('productbundlestotal_link'), 'token=' . $this->session->data['token'].$this->config->get('productbundlestotal_link_params'), 'SSL');
        
        /* Module-specific declarations - Begin */
        $this->load->language($this->modulePath);
        
        // Variables
        $this->data['moduleName'] 		= $this->moduleName;
        $this->data['modulePath']       = $this->modulePath;
        /* Module-specific loaders - End */
    }

	public function index() {
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting($this->moduleName, $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link($this->modulePath, 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->data['heading_title'] = $this->language->get('heading_title');
		
		$this->data['text_edit'] = $this->language->get('text_edit');
		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');

		$this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_total'),
			'href' => $this->extensionsLink
		);

		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->modulePath, 'token=' . $this->session->data['token'], 'SSL')
		);

		$this->data['action'] = $this->url->link($this->modulePath, 'token=' . $this->session->data['token'], 'SSL');

		$this->data['cancel'] = $this->extensionsLink;

		if (isset($this->request->post[$this->moduleName.'_status'])) {
			$this->data[$this->moduleName.'_status'] = $this->request->post[$this->moduleName.'_status'];
		} else {
			$this->data[$this->moduleName.'_status'] = $this->config->get($this->moduleName.'_status');
		}

		if (isset($this->request->post[$this->moduleName.'_sort_order'])) {
			$this->data[$this->moduleName.'_sort_order'] = $this->request->post[$this->moduleName.'_sort_order'];
		} else {
			$this->data[$this->moduleName.'_sort_order'] = $this->config->get($this->moduleName.'_sort_order');
		}

		$this->data['header'] = $this->load->controller('common/header');
		$this->data['column_left'] = $this->load->controller('common/column_left');
		$this->data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->modulePath . '.tpl', $this->data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', $this->modulePath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}