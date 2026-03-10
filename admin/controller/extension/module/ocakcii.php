<?php

class ControllerExtensionModuleocakcii extends Controller
{
  private $error = array();

  public function index()
  {
    $this->load->language('extension/module/ocakcii');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('setting/setting');

    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

      $this->model_setting_setting->editSetting("ocakcii", $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link('extension/module/ocakcii', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
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
      'href' => $this->url->link('extension/module/ocakcii', 'user_token=' . $this->session->data['user_token'], true),
    );

    $data['action'] = $this->url->link('extension/module/ocakcii', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    if ($this->request->server['REQUEST_METHOD'] != 'POST') {
      $module_info = $this->model_setting_setting->getSetting("ocakcii");
    }

    if (isset($this->request->post['status'])) {
      $data['status'] = $this->request->post['status'];
    } elseif (!empty($module_info)) {
      $data['status'] = $module_info['status'];
    } else {
      $data['status'] = '';
    }

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/ocakcii', $data));
  }

  public function list()
  {
    $this->load->language('extension/module/ocakcii');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/ocakcii');
    $this->load->model('setting/setting');

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
      'href' => $this->url->link('extension/module/ocakcii', 'user_token=' . $this->session->data['user_token'], true),
    );

    $data['insert'] = $this->url->link('extension/module/ocakcii/insert', 'user_token=' . $this->session->data['user_token'] . $url, true);
    $data['delete'] = $this->url->link('extension/module/ocakcii/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

    $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
    $limit = 30; // Указываем лимит элементов на странице

    // Рассчитываем начало выборки
    $start = ($page - 1) * $limit;

    // Получаем общее количество карт
    $total = $this->model_extension_module_ocakcii->getTotalAkcii();

    $results = $this->model_extension_module_ocakcii->getAkciaList($start, $limit);

    foreach ($results as $result) {

      if (isset($this->request->post['image'])) {
        $image = $this->request->post['image'];
      } elseif (!empty($result['image'])) {
        $image = $result['image'];
      } else {
        $image = 'no_image.png';
      }

      $this->load->model('tool/image');

      if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
        $thumb = $this->model_tool_image->resize($this->request->post['image'], 200, 200);
      } elseif (!empty($result['image']) && is_file(DIR_IMAGE . $result['image'])) {
        $thumb = $this->model_tool_image->resize($result['image'], 200, 200);
      } else {
        $thumb = $this->model_tool_image->resize('no_image.png', 200, 200);
      }

      $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 200, 200);

      $data['akcii'][] = array(
        'akcia_id'        => $result['akcia_id'],
        'name'            => $result['name'],
        'url'             => $result['url'],
        'image'           => $image,
        'thumb'           => $thumb,
        'status'          => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
        'selected'        => isset($this->request->post['selected']) && in_array($result['akcia_id'], $this->request->post['selected']),
        'edit'            => $this->url->link('extension/module/ocakcii/update', 'user_token=' . $this->session->data['user_token'] . '&akcia_id=' . $result['akcia_id'] . $url, true),
        'del'             => $this->url->link('extension/module/ocakcii/delete', 'user_token=' . $this->session->data['user_token'] . '&akcia_id=' . $result['akcia_id'] . $url, true)

      );
    }

    // Пагинация
    $pagination = new Pagination();
    $pagination->total = $total;
    $pagination->page = $page;
    $pagination->limit = $limit;
    $pagination->url = $this->url->link('extension/module/ocakcii/list', 'user_token=' . $this->session->data['user_token'] . '&page={page}');

    // Передаем URL для пагинации в данные
    $data['pagination'] = $pagination->render();

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/ocakcii_list', $data));
  }

  public function insert() {
    $this->language->load('extension/module/ocakcii');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/ocakcii');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
      $this->model_extension_module_ocakcii->addAkcia($this->request->post);

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

      $this->response->redirect($this->url->link('extension/module/ocakcii/list', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }

    $this->getForm();
  }

  public function update() {
    $this->language->load('extension/module/ocakcii');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/ocakcii');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {

      $this->model_extension_module_ocakcii->editAkcia((int)$this->request->get['akcia_id'], $this->request->post);

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
      $this->response->redirect($this->url->link('extension/module/ocakcii/list', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }

    $this->getForm();
  }

  public function delete() {

    $this->language->load('extension/module/ocakcii');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/ocakcii');

    if (isset($this->request->post['selected'])) {
      foreach ($this->request->post['selected'] as $akcia_id) {
        $this->model_extension_module_ocakcii->deleteAkcia($akcia_id);
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

      $this->response->redirect($this->url->link('extension/module/ocakcii/list', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }else{
      $this->model_extension_module_ocakcii->deleteAkcia($this->request->get['akcia_id']);
    }

    $this->list();
  }

  protected function getForm() {

    $this->language->load('extension/module/ocakcii');
    $this->load->model('extension/module/ocakcii');
    $this->load->model('tool/image');
    $this->load->model('localisation/language');
    $this->load->model('setting/store');

    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    if (isset($this->error['error_name'])) {
      $data['error_name'] = $this->error['error_name'];
    } else {
      $data['error_name'] = '';
    }

    if (isset($this->error['error_keyword'])) {
      $data['error_keyword'] = $this->error['error_keyword'];
    } else {
      $data['error_keyword'] = '';
    }

    if (isset($this->error['error_desc'])) {
      $data['error_desc'] = $this->error['error_desc'];
    } else {
      $data['error_desc'] = '';
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
      'href' => $this->url->link('extension/module/ocakcii/list', 'user_token=' . $this->session->data['user_token'], true)
    );

    if (!isset($this->request->get['akcia_id'])) {
      $data['breadcrumbs'][] = array(
        'text'      => $this->language->get('heading_title'),
        'href'      => $this->url->link('extension/module/ocakcii/insert', 'user_token=' . $this->session->data['user_token'] . $url, true)
      );

      $data['action'] = $this->url->link('extension/module/ocakcii/insert', 'user_token=' . $this->session->data['user_token'] . $url, true);
    } else {
      $data['breadcrumbs'][] = array(
        'text'      => $this->language->get('heading_title'),
        'href'      => $this->url->link('extension/module/ocakcii/update', 'user_token=' . $this->session->data['user_token'] . '&akcia_id=' . $this->request->get['akcia_id'] . $url, true)
      );

      $data['action'] = $this->url->link('extension/module/ocakcii/update', 'user_token=' . $this->session->data['user_token'] . '&akcia_id=' . $this->request->get['akcia_id'] . $url, true);
    }

    $data['cancel'] = $this->url->link('extension/module/ocakcii', 'user_token=' . $this->session->data['user_token'] . $url, true);

    if (isset($this->request->get['akcia_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
      $akcia_info = $this->model_extension_module_ocakcii->getAkciaId($this->request->get['akcia_id']);
      $data['akcia_id'] = $akcia_info['akcia_id'];
    }

    $data['user_token'] = $this->session->data['user_token'];

    $data['stores'] = array();
    $data['stores'][] = array(
      'store_id' => 0,
      'name'     => $this->language->get('text_default')
    );
    $stores = $this->model_setting_store->getStores();
    foreach ($stores as $store) {
      $data['stores'][] = array(
        'store_id' => $store['store_id'],
        'name'     => $store['name']
      );
    }

    if (isset($this->request->post['akcia_store'])) {
      $data['akcia_store'] = $this->request->post['akcia_store'];
    } elseif (!empty($akcia_info)) {
      $data['akcia_store'] = $this->model_extension_module_ocakcii->getAkciaStores($akcia_info['akcia_id']);
    } else {
      $data['akcia_store'] = array(0);
    }

    if (isset($this->request->post['status'])) {
      $data['status'] = $this->request->post['status'];
    } elseif (!empty($akcia_info)) {
      $data['status'] = $akcia_info['status'];
    } else {
      $data['status'] = '';
    }

    if (isset($this->request->post['guid'])) {
      $data['guid'] = $this->request->post['guid'];
    } elseif (!empty($akcia_info)) {
      $data['guid'] = $akcia_info['guid'];
    } else {
      $data['guid'] = '';
    }

    if (isset($this->request->post['sort_order'])) {
      $data['sort_order'] = $this->request->post['sort_order'];
    } elseif (!empty($akcia_info)) {
      $data['sort_order'] = $akcia_info['sort_order'];
    } else {
      $data['sort_order'] = '';
    }

    if (isset($this->request->post['lang'])) {
      $data['lang'] = $this->request->post['lang'];
    } elseif (!empty($akcia_info)) {
      $data['lang'] = $this->model_extension_module_ocakcii->getAkciaDescriptions($akcia_info['akcia_id']);
    } else {
      $data['lang'] = '';
    }

    if (isset($this->request->post['date_start'])) {
      $data['date_start'] = $this->request->post['date_start'];
    } elseif (!empty($akcia_info)) {
      $data['date_start'] = ($akcia_info['date_start'] != '0000-00-00' ? $akcia_info['date_start'] : '');
    } else {
      $data['date_start'] = date('Y-m-d', time());
    }

    if (isset($this->request->post['date_end'])) {
      $data['date_end'] = $this->request->post['date_end'];
    } elseif (!empty($akcia_info)) {
      $data['date_end'] = ($akcia_info['date_end'] != '0000-00-00' ? $akcia_info['date_end'] : '');
    } else {
      $data['date_end'] = date('Y-m-d', strtotime('+1 month'));
    }

    if (isset($this->request->post['image'])) {
      $data['image'] = $this->request->post['image'];
    } elseif (!empty($akcia_info)) {
      $data['image'] = $akcia_info['image'];
    } else {
      $data['image'] = $this->model_tool_image->resize('no_image.png', 100, 100);
    }

    if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
      $data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
    } elseif (!empty($akcia_info) && is_file(DIR_IMAGE . $akcia_info['image'])) {
      $data['thumb'] = $this->model_tool_image->resize($akcia_info['image'], 100, 100);
    } else {
      $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
    }

    if (isset($this->request->post['akcii_seo_url'])) {
      $data['akcii_seo_url'] = $this->request->post['akcii_seo_url'];
    } elseif (!empty($akcia_info)) {
      $data['akcii_seo_url'] = $this->model_extension_module_ocakcii->getAkciaSeoUrls($akcia_info['akcia_id']);
    } else {
      $data['akcii_seo_url'] = array();
    }

    $data['languages'] = $this->model_localisation_language->getLanguages();

    $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/ocakcii_form', $data));
  }

  protected function validateForm() {
    if (!$this->user->hasPermission('modify', 'extension/module/ocakcii')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    foreach ($this->request->post['lang'] as $language_id => $value) {
      if ((utf8_strlen($value['desc']) < 3) || (utf8_strlen($value['desc']) > 255)) {
        $this->error['desc'][$language_id] = $this->language->get('error_desc');
      }

      if (empty($value['name'])) {
        $this->error['error_name'][$language_id] = $this->language->get('error_name');
      }
    }

    if ($this->request->post['akcii_seo_url']) {
      $this->load->model('design/seo_url');

      foreach ($this->request->post['akcii_seo_url'] as $store_id => $language) {
        foreach ($language as $language_id => $keyword) {
          if (!empty($keyword)) {
            if (count(array_keys($language, $keyword)) > 1) {
              $this->error['keyword'][$store_id][$language_id] = $this->language->get('error_unique');
            }

            $seo_urls = $this->model_design_seo_url->getSeoUrlsByKeyword($keyword);

            foreach ($seo_urls as $seo_url) {
              if (($seo_url['store_id'] == $store_id) && (!isset($this->request->get['akcia_id']) || (($seo_url['query'] != 'akcia_id=' . $this->request->get['akcia_id'])))) {
                $this->error['keyword'][$store_id][$language_id] = $this->language->get('error_keyword');

                break;
              }
            }
          }else{
            $this->error['error_keyword'][$language_id] = $this->language->get('error_keyword_empty');
          }
        }
      }
    }

    if ($this->error && !isset($this->error['warning'])) {
      $this->error['warning'] = $this->language->get('error_warning');
    }

    return !$this->error;
  }

  public function install()
  {
    $this->load->model('extension/module/ocakcii');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');
    $this->load->model('user/user_group');

    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/ocakcii');
    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/ocakcii');

    $this->model_extension_module_ocakcii->install();

    $data = array(
      'module_ocakcii_status' => 1
    );

    $this->model_setting_setting->editSetting('ocakcii', $data, 0);
  }

  public function uninstall()
  {
    $this->load->model('extension/module/ocakcii');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');
    $this->load->model('user/user_group');

    $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/module/ocakcii');
    $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/module/ocakcii');

    $this->model_extension_module_ocakcii->uninstall();
    $this->model_setting_extension->uninstall('ocakcii', $this->request->get['extension']);
    $this->model_setting_setting->deleteSetting($this->request->get['extension']);
  }
}
