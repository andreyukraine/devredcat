<?php

class ControllerExtensionModuleOccards extends Controller
{
  private $error = array();

  public function index()
  {
    $this->load->language('extension/module/occards');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('setting/setting');
    $this->load->model('setting/module');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      if (!isset($this->request->get['module_id'])) {
        $this->model_setting_module->addModule('occards', $this->request->post);
      } else {
        $this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
      }

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
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
      'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/module/occards', 'user_token=' . $this->session->data['user_token'], true),
    );


    $data['action'] = $this->url->link('extension/module/occards', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
      $module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
      $data['action'] = $this->url->link('extension/module/occards', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
    }

    if (isset($this->request->post['name'])) {
      $data['name'] = $this->request->post['name'];
    } elseif (!empty($module_info)) {
      $data['name'] = $module_info['name'];
    } else {
      $data['name'] = '';
    }

    if (isset($this->request->post['status'])) {
      $data['status'] = $this->request->post['status'];
    } elseif (!empty($module_info)) {
      $data['status'] = $module_info['status'];
    } else {
      $data['status'] = '';
    }

    if (isset($this->error['name'])) {
      $data['error_name'] = $this->error['name'];
    } else {
      $data['error_name'] = '';
    }

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/occards', $data));
  }

  protected function validate()
  {
//        if (!$this->user->hasPermission('modify', 'extension/module/ocblog')) {
//            $this->error['warning'] = $this->language->get('error_permission');
//        }
//
//        if (!$this->request->post['width']) {
//            $this->error['width'] = $this->language->get('error_width');
//        }
//
//        if (!$this->request->post['height']) {
//            $this->error['height'] = $this->language->get('error_height');
//        }
//
//        if ($this->error && !isset($this->error['warning'])) {
//            $this->error['warning'] = $this->language->get('error_warning');
//        }
//
//        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
//            $this->error['name'] = $this->language->get('error_name');
//        }

//        return !$this->error;
  }

  public function install()
  {
    $this->load->model('extension/module/occards');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');

//        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'blog/article');
//        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'blog/article');
//
//        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'blog/articlelist');
//        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'blog/articlelist');
//
//        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'blog/config');
//        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'blog/config');

    $this->model_extension_module_occards->install();

    $data = array(
      'module_occards_article_limit' => '10',
      'module_occards_meta_title' => 'Cards',
    );

    $this->model_setting_setting->editSetting('module_occards', $data, 0);
  }

  public function list()
  {
    $this->load->language('extension/module/occards');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/occards');
    $this->load->model('setting/setting');
    $this->load->model('setting/module');

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
      'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/module/occards', 'user_token=' . $this->session->data['user_token'], true),
    );

    $data['action'] = $this->url->link('extension/module/occards', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
    $limit = 30; // Указываем лимит элементов на странице

    // Рассчитываем начало выборки
    $start = ($page - 1) * $limit;

    // Получаем общее количество карт
    $total = $this->model_extension_module_occards->getTotalCards();

    $data['cards'] = $this->model_extension_module_occards->getCardList($start, $limit);

    if (isset($this->request->post['name'])) {
      $data['name'] = $this->request->post['name'];
    } elseif (!empty($module_info)) {
      $data['name'] = $module_info['name'];
    } else {
      $data['name'] = '';
    }

    if (isset($this->request->post['status'])) {
      $data['status'] = $this->request->post['status'];
    } elseif (!empty($module_info)) {
      $data['status'] = $module_info['status'];
    } else {
      $data['status'] = '';
    }

    if (isset($this->error['name'])) {
      $data['error_name'] = $this->error['name'];
    } else {
      $data['error_name'] = '';
    }

    // Пагинация
    $pagination = new Pagination();
    $pagination->total = $total;
    $pagination->page = $page;
    $pagination->limit = $limit;
    $pagination->url = $this->url->link('extension/module/occards/list', 'user_token=' . $this->session->data['user_token'] . '&page={page}');

    // Передаем URL для пагинации в данные
    $data['pagination'] = $pagination->render();

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/occard_list', $data));
  }


  public function uninstall()
  {
    $this->load->model('extension/module/occards');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');

    $this->model_extension_module_occards->uninstall();
    $this->model_setting_extension->uninstall('module_occards', $this->request->get['extension']);
    $this->model_setting_setting->deleteSetting($this->request->get['extension']);
  }
}
