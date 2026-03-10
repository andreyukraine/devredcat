<?php

class ControllerExtensionModuleOcpolicy extends Controller
{
  private $error = [];

  public function index()
  {
    $this->load->language('extension/module/ocpolicy');

    //Add Codemirror Styles && Scripts
    $this->document->addScript('view/javascript/codemirror/lib/codemirror.js');
    $this->document->addScript('view/javascript/codemirror/lib/xml.js');
    $this->document->addScript('view/javascript/codemirror/lib/formatting.js');
    $this->document->addStyle('view/javascript/codemirror/lib/codemirror.css');
    $this->document->addStyle('view/javascript/codemirror/theme/monokai.css');

    //Add Summernote Styles && Scripts
    $this->document->addScript('view/javascript/summernote/summernote.js');
    $this->document->addScript('view/javascript/summernote/summernote-image-attributes.js');
    $this->document->addScript('view/javascript/summernote/opencart.js');
    $this->document->addStyle('view/javascript/summernote/summernote.css');
    $this->document->addScript('view/javascript/bootstrap-notify/bootstrap-notify.min.js');
    $this->document->addStyle('view/stylesheet/stylesheet.css');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/setting');
    $this->load->model('localisation/language');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('ocpolicy', $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');
      $this->response->redirect($this->url->link('extension/module/ocpolicy', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
    }

    $data['breadcrumbs'] = [];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
    ];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/module/ocpolicy', 'user_token=' . $this->session->data['user_token'], true)
    ];

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

    $data['action'] = $this->url->link('extension/module/ocpolicy', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    $data['user_token'] = $this->session->data['user_token'];
    $data['languages'] = $this->model_localisation_language->getLanguages();

    $this->load->model('catalog/information');

    $data['informations'] = [];

    $filter_data = [
      'sort' => 'id.title',
      'order' => 'ASC',
      'start' => 0,
      'limit' => 10000
    ];

    $informations_info = $this->model_catalog_information->getInformations($filter_data);

    foreach ($informations_info as $result) {
      $data['informations'][] = [
        'information_id' => $result['information_id'],
        'title' => $result['title']
      ];
    }

    if (isset($this->request->post['ocpolicy_status'])) {
      $data['ocpolicy_status'] = $this->request->post['ocpolicy_status'];
    } else {
      $data['ocpolicy_status'] = $this->config->get('ocpolicy_status');
    }

    if (isset($this->request->post['ocpolicy_data'])) {
      $data['ocpolicy_data'] = $this->request->post['ocpolicy_data'];
    } else {
      $data['ocpolicy_data'] = $this->config->get('ocpolicy_data');
    }

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/ocpolicy', $data));
  }

  public function install()
  {
    $this->load->language('extension/module/ocpolicy');

    $this->load->model('setting/setting');
    $this->load->model('localisation/language');
    $this->load->model('user/user_group');

    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/ocpolicy');
    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/ocpolicy');

    $results = $this->model_localisation_language->getLanguages();

    $module_text = [];

    foreach ($results as $result) {
      $module_text[$result['language_id']] = '';
    }

    $this->model_setting_setting->editSetting('ocpolicy', [
        'ocpolicy_status' => '1',
        'ocpolicy_data' => [
        'indormation_id' => 0,
        'max_day' => 7,
        'value' => 'ocpolicy',
        'module_text' => $module_text
      ]
    ]);

    $this->session->data['success'] = $this->language->get('text_success_install');

    $this->response->redirect($this->url->link('extension/module/ocpolicy', 'user_token=' . $this->session->data['user_token'], true));
  }

  public function uninstall()
  {
    $this->load->model('setting/setting');
    $this->load->model('user/user_group');

    $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/module/ocpolicy');
    $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/module/ocpolicy');

    $this->model_setting_setting->deleteSetting('ocpolicy');
  }

  protected function validate()
  {
    if (!$this->user->hasPermission('modify', 'extension/module/ocpolicy')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    return !$this->error;
  }
}
