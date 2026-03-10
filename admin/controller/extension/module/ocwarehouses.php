<?php

class ControllerExtensionModuleOcwarehouses extends Controller
{
  private $error = array();

  public function install()
  {
    $this->load->model('extension/module/ocwarehouses');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');

    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/ocwarehouses');
    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/ocwarehouses');

    $this->model_extension_module_ocwarehouses->install();

    $data = array(
      'module_occards_article_limit' => '10',
      'module_occards_meta_title' => 'Warehouses',
    );

    $this->model_setting_setting->editSetting('module_ocwarehouses', $data, 0);
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

  public function uninstall()
  {
    $this->load->model('extension/module/ocwarehouses');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');

    $this->model_extension_module_ocwarehouses->uninstall();
    $this->model_setting_extension->uninstall('module_ocwarehouses', $this->request->get['extension']);
    $this->model_setting_setting->deleteSetting($this->request->get['extension']);
  }

  public function index()
  {
    $this->load->language('extension/module/ocwarehouses');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('setting/setting');
    $this->load->model('setting/module');

    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
      if (!isset($this->request->get['module_id'])) {
        $this->model_setting_module->addModule('ocwarehouses', $this->request->post);
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
      'href' => $this->url->link('extension/module/ocwarehouses', 'user_token=' . $this->session->data['user_token'], true),
    );


    $data['action'] = $this->url->link('extension/module/ocwarehouses', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
      $module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
      $data['action'] = $this->url->link('extension/module/ocwarehouses', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
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

    $this->response->setOutput($this->load->view('extension/module/ocwarehouses', $data));
  }

  public function list()
  {
    $this->load->language('extension/module/ocwarehouses');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/ocwarehouses');
    $this->load->model('setting/setting');
    $this->load->model('setting/module');

    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
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
      'text' => $this->language->get('text_extension'),
      'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/module/ocwarehouses', 'user_token=' . $this->session->data['user_token'], true),
    );

    $data['action'] = $this->url->link('extension/module/ocwarehouses', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    $results = $this->model_extension_module_ocwarehouses->getWarehouseList();

    foreach ($results as $result) {
      $action = array();

      $action[] = array(
        'text' => $this->language->get('text_edit'),
        'href' => $this->url->link('extension/module/ocwarehouses/update', 'user_token=' . $this->session->data['user_token'] . '&warehouse_id=' . $result['warehouse_id'] . $url, true),
        'del' => $this->url->link('extension/module/ocwarehouses/delete', 'user_token=' . $this->session->data['user_token'] . '&warehouse_id=' . $result['warehouse_id'] . $url, true)
      );

      if (isset($this->request->post['image'])) {
        $image = $this->request->post['image'];
      } elseif (!empty($result['image'])) {
        $image = $result['image'];
      } else {
        $image = 'no_image.png';
      }

      $this->load->model('tool/image');

      if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
        $thumb = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
      } elseif (!empty($result['image']) && is_file(DIR_IMAGE . $result['image'])) {
        $thumb = $this->model_tool_image->resize($result['image'], 100, 100);
      } else {
        $thumb = $this->model_tool_image->resize('no_image.png', 100, 100);
      }

      $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

      $data['warehouses'][] = array(
        'warehouse_id'    => $result['warehouse_id'],
        'uid'             => $result['uid'],
        'name'            => $result['name'],
        'phone'           => $result['phone'],
        'address'         => $result['address'],
        'working_hours'   => $result['working_hours'],
        'image'           => $image,
        'thumb'           => $thumb,
        'status'          => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
        'selected'        => isset($this->request->post['selected']) && in_array($result['warehouse_id'], $this->request->post['selected']),
        'action'          => $action
      );
    }

    $data['insert'] = $this->url->link('extension/module/ocwarehouses/insert', 'user_token=' . $this->session->data['user_token'] . $url, true);
    $data['delete'] = $this->url->link('extension/module/ocwarehouses/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);


    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/ocwarehouses_list', $data));
  }

  public function insert() {
    $this->language->load('extension/module/ocwarehouses');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/ocwarehouses');

    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
      $this->model_extension_module_ocwarehouses->addWarehouse($this->request->post);

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

      $this->response->redirect($this->url->link('extension/module/ocwarehouses/list', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }

    $this->getForm();
  }

  protected function getForm() {

    $this->load->model('tool/image');

    if (isset($this->request->post['ocwarehouses_module'])) {
      $modules = $this->request->post['ocwarehouses_module'];
    } elseif ($this->config->has('ocwarehouses_module')) {
      $modules = $this->config->get('ocwarehouses_module');
    } else {
      $modules = array();
    }

    $data['ocwarehouses_modules'] = array();

    foreach ($modules as $key => $module) {
      $data['ocwarehouses_modules'][] = array(
        'key'       => $key,
        'width'     => $module['width'],
        'height'    => $module['height']
      );
    }

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

    if (isset($this->error['delay'])) {
      $data['error_delay'] = $this->error['delay'];
    } else {
      $data['error_delay'] = '';
    }

    if (isset($this->error['ocwarehouses_image'])) {
      $data['error_ocwarehouses_image'] = $this->error['ocwarehouses_image'];
    } else {
      $data['error_ocwarehouses_image'] = array();
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
      'text'      => $this->language->get('text_home'),
      'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true)
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_list'),
      'href' => $this->url->link('extension/module/ocwarehouses/list', 'user_token=' . $this->session->data['user_token'], true)
    );

    if (!isset($this->request->get['warehouse_id'])) {
      $data['breadcrumbs'][] = array(
        'text'      => $this->language->get('heading_title'),
        'href'      => $this->url->link('extension/module/ocwarehouses/insert', 'user_token=' . $this->session->data['user_token'] . $url, true)
      );

      $data['action'] = $this->url->link('extension/module/ocwarehouses/insert', 'user_token=' . $this->session->data['user_token'] . $url, true);
    } else {
      $data['breadcrumbs'][] = array(
        'text'      => $this->language->get('heading_title'),
        'href'      => $this->url->link('extension/module/ocwarehouses/update', 'user_token=' . $this->session->data['user_token'] . '&warehouse_id=' . $this->request->get['warehouse_id'] . $url, true)
      );

      $data['action'] = $this->url->link('extension/module/ocwarehouses/update', 'user_token=' . $this->session->data['user_token'] . '&warehouse_id=' . $this->request->get['warehouse_id'] . $url, true);
    }

    $data['cancel'] = $this->url->link('extension/module/ocwarehouses', 'user_token=' . $this->session->data['user_token'] . $url, true);

    if (isset($this->request->get['warehouse_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
      $warehouse_info = $this->model_extension_module_ocwarehouses->getWarehouseId($this->request->get['warehouse_id']);
      $data['warehouse_id'] = $warehouse_info['warehouse_id'];
    }

    $data['user_token'] = $this->session->data['user_token'];

    if (isset($this->request->post['status'])) {
      $data['status'] = $this->request->post['status'];
    } elseif (!empty($warehouse_info)) {
      $data['status'] = $warehouse_info['status'];
    } else {
      $data['status'] = '';
    }

    if (isset($this->request->post['uid'])) {
      $data['uid'] = $this->request->post['uid'];
    } elseif (!empty($warehouse_info)) {
      $data['uid'] = $warehouse_info['uid'];
    } else {
      $data['uid'] = '';
    }

    if (isset($this->request->post['name'])) {
      $data['name'] = $this->request->post['name'];
    } elseif (!empty($warehouse_info)) {
      $data['name'] = $warehouse_info['name'];
    } else {
      $data['name'] = '';
    }

    if (isset($this->request->post['phone'])) {
      $data['phone'] = $this->request->post['phone'];
    } elseif (!empty($warehouse_info)) {
      $data['phone'] = $warehouse_info['phone'];
    } else {
      $data['phone'] = '';
    }

    if (isset($this->request->post['image'])) {
      $data['image'] = $this->request->post['image'];
    } elseif (!empty($warehouse_info)) {
      $data['image'] = $warehouse_info['image'];
    } else {
      $data['image'] = $this->model_tool_image->resize('no_image.png', 100, 100);
    }

    if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
      $data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
    } elseif (!empty($warehouse_info) && is_file(DIR_IMAGE . $warehouse_info['image'])) {
      $data['thumb'] = $this->model_tool_image->resize($warehouse_info['image'], 100, 100);
    } else {
      $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
    }

    $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

    if (isset($this->request->post['lat'])) {
      $data['lat'] = $this->request->post['lat'];
    } elseif (!empty($warehouse_info)) {
      $data['lat'] = $warehouse_info['lat'];
    } else {
      $data['lat'] = '';
    }

    if (isset($this->request->post['lon'])) {
      $data['lon'] = $this->request->post['lon'];
    } elseif (!empty($warehouse_info)) {
      $data['lon'] = $warehouse_info['lon'];
    } else {
      $data['lon'] = '';
    }

    if (isset($this->request->post['working_hours'])) {
      $data['working_hours'] = $this->request->post['working_hours'];
    } elseif (!empty($warehouse_info)) {
      $data['working_hours'] = $warehouse_info['working_hours'];
    } else {
      $data['working_hours'] = '';
    }

    if (isset($this->request->post['address'])) {
      $data['address'] = $this->request->post['address'];
    } elseif (!empty($warehouse_info)) {
      $data['address'] = $warehouse_info['address'];
    } else {
      $data['address'] = '';
    }

    $this->load->model('localisation/language');
    $this->load->model('tool/image');
    $this->load->model('setting/store');

    $data['languages'] = $this->model_localisation_language->getLanguages();

    $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/ocwarehouses_form', $data));
  }

  public function update() {
    $this->language->load('extension/module/ocwarehouses');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/ocwarehouses');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') ) {

      $this->model_extension_module_ocwarehouses->editWarehouseId((int)$this->request->get['warehouse_id'], $this->request->post);

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
      $this->response->redirect($this->url->link('extension/module/ocwarehouses/list', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }

    $this->getForm();
  }

  public function delete() {

    $this->language->load('extension/module/ocwarehouses');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/ocwarehouses');

    if (isset($this->request->post['selected'])) {
      foreach ($this->request->post['selected'] as $warehouse_id) {
        $this->model_extension_module_ocwarehouses->deleteWarehouse($warehouse_id);
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

      $this->response->redirect($this->url->link('extension/module/ocwarehouses/list', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }else{
      $this->model_extension_module_ocwarehouses->deleteWarehouse($this->request->get['warehouse_id']);
    }

    $this->list();
  }

}
