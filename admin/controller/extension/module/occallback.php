<?php

class ControllerExtensionModuleOccallback extends Controller
{
  private $error = [];

  public function index()
  {
    $this->load->language('extension/module/occallback');
    $this->document->addScript('view/javascript/bootstrap-notify/bootstrap-notify.min.js');
    $this->document->addStyle('view/stylesheet/stylesheet.css');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/setting');

    $this->load->model('localisation/language');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('occallback', $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link('extension/module/occallback', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
    }

    $data['breadcrumbs'] = [];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
    ];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/module/occallback', 'user_token=' . $this->session->data['user_token'], true)
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

    $data['action'] = $this->url->link('extension/module/occallback', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    $data['user_token'] = $this->session->data['user_token'];

    if (isset($this->error['notify_email'])) {
      $data['error_notify_email'] = $this->error['notify_email'];
    } else {
      $data['error_notify_email'] = '';
    }

    if (isset($this->request->post['occallback_status'])) {
      $data['occallback_status'] = $this->request->post['occallback_status'];
    } else {
      $data['occallback_status'] = $this->config->get('occallback_status');
    }

    if (isset($this->request->post['occallback_data'])) {
      $data['occallback_data'] = $this->request->post['occallback_data'];
    } else {
      $data['occallback_data'] = $this->config->get('occallback_data');
    }

    //main bot
    if (isset($this->request->post['occallback_main_bot'])) {
      $data['occallback_main_bot'] = $this->request->post['occallback_main_bot'];
    } else {
      $data['occallback_main_bot'] = $this->config->get('occallback_main_bot');
    }
    if (isset($this->request->post['occallback_main_bot_users'])) {
      $data['occallback_main_bot_users'] = $this->request->post['occallback_main_bot_users'];
    } else {
      $data['occallback_main_bot_users'] = $this->config->get('occallback_main_bot_users');
    }

    //manager bot
    if (isset($this->request->post['occallback_manager_bot'])) {
      $data['occallback_manager_bot'] = $this->request->post['occallback_manager_bot'];
    } else {
      $data['occallback_manager_bot'] = $this->config->get('occallback_manager_bot');
    }
    if (isset($this->request->post['occallback_manager_bot_users'])) {
      $data['occallback_manager_bot_users'] = $this->request->post['occallback_manager_bot_users'];
    } else {
      $data['occallback_manager_bot_users'] = $this->config->get('occallback_manager_bot_users');
    }

    $data['is_admin'] = (int)$this->user->getId() == 1;

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/occallback', $data));
  }

  public function history()
  {
    $data = [];
    $this->load->model('extension/module/occallback');
    $this->load->language('extension/module/occallback');

    $page = (isset($this->request->get['page'])) ? $this->request->get['page'] : 1;
    $data['user_token'] = $this->session->data['user_token'];

    $data['histories'] = [];

    $filter_data = [
      'start' => ($page - 1) * 20,
      'limit' => 20,
      'sort' => 'r.date_added',
      'order' => 'DESC'
    ];

    $results = $this->model_extension_module_occallback->getCallArray($filter_data);

    foreach ($results as $result) {
      $info = [];

      $fields = unserialize($result['info']);

      foreach ($fields as $field) {
        $info[] = [
          'name' => $field['name'],
          'value' => $field['value']
        ];
      }

      $data['histories'][] = [
        'request_id' => $result['request_id'],
        'info' => $info,
        'processed' => $result['processed'],
        'date_added' => $result['date_added']
      ];
    }

    $history_total = $this->model_extension_module_occallback->getTotalCallArray();

    $pagination = new Pagination();
    $pagination->total = $history_total;
    $pagination->page = $page;
    $pagination->limit = 20;
    $pagination->url = $this->url->link('extension/module/occallback/history', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

    $data['pagination'] = $pagination->render();

    $data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * 20) + 1 : 0, ((($page - 1) * 20) > ($history_total - 20)) ? $history_total : ((($page - 1) * 20) + 20), $history_total, ceil($history_total / 20));

    $data['is_admin'] = (int)$this->user->getId() == 1;

    $this->response->setOutput($this->load->view('extension/module/occallback_history', $data));
  }

  public function history_add_processed()
  {
    $json = [];
    $this->load->model('extension/module/occallback');
    $this->load->language('extension/module/occallback');

    $results = $this->model_extension_module_occallback->addProcessed((int)$this->request->get['request_id'], (int)$this->request->get['processed_status']);

    $json['output'] = $results;

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function delete_selected()
  {
    $json = [];
    $this->load->model('extension/module/occallback');

    $info = $this->model_extension_module_occallback->getCall((int)$this->request->get['delete']);

    if ($info) {
      $this->model_extension_module_occallback->deleteCall((int)$this->request->get['delete']);
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function delete_all()
  {
    $json = [];
    $this->load->model('extension/module/occallback');

    $this->model_extension_module_occallback->deleteAllCallArray();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function delete_all_selected()
  {
    $json = [];
    $this->load->model('extension/module/occallback');

    if (isset($this->request->request['selected'])) {
      foreach ($this->request->request['selected'] as $request_id) {
        $info = $this->model_extension_module_occallback->getCall((int)$request_id);

        if ($info) {
          $this->model_extension_module_occallback->deleteCall((int)$request_id);
        }
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function install()
  {
    $this->load->language('extension/module/occallback');

    $this->load->model('extension/module/occallback');
    $this->load->model('setting/setting');
    $this->load->model('user/user_group');

    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/occallback');
    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/occallback');

    $this->model_extension_module_occallback->makeDB();

    $this->model_setting_setting->editSetting('occallback', [
        'occallback_status' => '1',
        'occallback_data' => [
        'notify_status' => '1',
        'notify_email' => $this->config->get('config_email'),
        'name' => '2',
        'telephone' => '2',
        'comment' => '1',
        'mask' => '8 (999) 999-99-99'
      ]
    ]);

    $this->session->data['success'] = $this->language->get('text_success_install');

    $this->response->redirect($this->url->link('extension/module/occallback', 'user_token=' . $this->session->data['user_token'], true));
  }

  public function uninstall()
  {
    $this->load->model('setting/setting');
    $this->load->model('extension/module/occallback');
    $this->load->model('user/user_group');

    $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/module/occallback');
    $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/module/occallback');

    $this->model_extension_module_occallback->deleteDB();

    $this->model_setting_setting->deleteSetting('occallback');
  }

  protected function validate()
  {
    if (!$this->user->hasPermission('modify', 'extension/module/occallback')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    foreach ($this->request->post['occallback_data'] as $key => $field) {
      if (empty($field) && $key == "notify_email") {
        $this->error['notify_email'] = $this->language->get('error_notify_email');
      }
    }

    return !$this->error;
  }
}
