<?php

class ControllerExtensionModuleOccategoryPopular extends Controller
{
  private $error = array();

  public function index()
  {
    $this->load->language('extension/module/occategory_popular');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('setting/setting');
    $this->load->model('setting/module');

    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
      if (!isset($this->request->get['module_id'])) {
        $this->model_setting_module->addModule('occategory_popular', $this->request->post);
      } else {
        $this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
      }

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
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
      'href' => $this->url->link('extension/module/occategory_popular', 'user_token=' . $this->session->data['user_token'], true),
    );


    $data['action'] = $this->url->link('extension/module/occategory_popular', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
      $module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
      $data['action'] = $this->url->link('extension/module/occategory_popular', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
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

    $this->response->setOutput($this->load->view('extension/module/occategory_popular', $data));
  }

  public function add() {
    $this->load->language('extension/module/occategory_popular');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('extension/module/occategory_popular');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_extension_module_occategory_popular->addCategory($this->request->post);

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

      $this->response->redirect($this->url->link('extension/module/occategory_popular/list', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }

    $this->getForm();
  }

  public function edit() {
    $this->load->language('extension/module/occategory_popular');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('extension/module/occategory_popular');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_extension_module_occategory_popular->editCategory($this->request->get['category_popular_id'], $this->request->post);

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

      $this->response->redirect($this->url->link('extension/module/occategory_popular/list', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }

    $this->getForm();
  }

  public function delete() {
    $this->load->language('extension/module/occategory_popular');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('extension/module/occategory_popular');

    if (isset($this->request->post['selected'])) {
      foreach ($this->request->post['selected'] as $category_popular_id) {
        $this->model_extension_module_occategory_popular->deleteCategory($category_popular_id);
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

      $this->response->redirect($this->url->link('extension/module/occategory_popular/list', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }

    $this->list();
  }

  public function list()
  {
    $this->load->language('extension/module/occategory_popular');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/occategory_popular');
    $this->load->model('setting/setting');
    $this->load->model('setting/module');

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

    if (!isset($this->request->get['category_popular_id'])) {
      $data['breadcrumbs'][] = array(
        'text'      => $this->language->get('heading_title'),
        'href'      => $this->url->link('extension/module/occategory_popular/insert', 'user_token=' . $this->session->data['user_token'] . $url, true)
      );

      $data['action'] = $this->url->link('extension/module/occategory_popular/insert', 'user_token=' . $this->session->data['user_token'] . $url, true);
    } else {
      $data['breadcrumbs'][] = array(
        'text'      => $this->language->get('heading_title'),
        'href'      => $this->url->link('extension/module/occategory_popular/update', 'user_token=' . $this->session->data['user_token'] . '&category_popular_id=' . $this->request->get['category_popular_id'] . $url, true)
      );

      $data['action'] = $this->url->link('extension/module/occategory_popular/update', 'user_token=' . $this->session->data['user_token'] . '&category_popular_id=' . $this->request->get['category_popular_id'] . $url, true);
    }

    $data['add'] = $this->url->link('extension/module/occategory_popular/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
    $data['delete'] = $this->url->link('extension/module/occategory_popular/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

    $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
    $limit = 30; // Указываем лимит элементов на странице

    // Рассчитываем начало выборки
    $start = ($page - 1) * $limit;

    // Получаем общее количество карт
    $total = $this->model_extension_module_occategory_popular->getTotalCategory();

    $data['categories'] = array();

    $categories = $this->model_extension_module_occategory_popular->getCategoryList($start, $limit);
    foreach ($categories as $category) {
      $this->load->model('catalog/category');
      $category_info = $this->model_catalog_category->getCategory((int)$category['category_id']);
      if ($category_info) {
        $data['categories'][] = array(
          'category_popular_id' => $category['category_popular_id'],
          "name" => $category_info['name'],
          "status" => $category['status'],
          "sort_order" => $category['sort_order'],
          'edit'            => $this->url->link('extension/module/occategory_popular/edit', 'user_token=' . $this->session->data['user_token'] . '&category_popular_id=' . $category['category_popular_id'] . $url, true),
          'del'             => $this->url->link('extension/module/occategory_popular/delete', 'user_token=' . $this->session->data['user_token'] . '&category_popular_id=' . $category['category_popular_id'] . $url, true)
        );
      }
    }

    // Пагинация
    $pagination = new Pagination();
    $pagination->total = $total;
    $pagination->page = $page;
    $pagination->limit = $limit;
    $pagination->url = $this->url->link('extension/module/occategory_popular/list', 'user_token=' . $this->session->data['user_token'] . '&page={page}');

    // Передаем URL для пагинации в данные
    $data['pagination'] = $pagination->render();

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/occategory_popular_list', $data));
  }

  protected function getForm() {

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
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/module/occategory_popular/list', 'user_token=' . $this->session->data['user_token'] . $url, true)
    );

    if (!isset($this->request->get['category_popular_id'])) {
      $data['action'] = $this->url->link('extension/module/occategory_popular/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
    } else {
      $data['action'] = $this->url->link('extension/module/occategory_popular/edit', 'user_token=' . $this->session->data['user_token'] . '&category_popular_id=' . $this->request->get['category_popular_id'] . $url, true);
    }

    $data['cancel'] = $this->url->link('extension/module/occategory_popular', 'user_token=' . $this->session->data['user_token'] . $url, true);


    if (isset($this->request->get['category_popular_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
      $category_popular_info = $this->model_extension_module_occategory_popular->getCategory($this->request->get['category_popular_id']);
    }

    $data['user_token'] = $this->session->data['user_token'];

    if (isset($this->request->post['category_id'])) {
      $data['category_id'] = $this->request->post['category_id'];
    } elseif (!empty($category_popular_info)) {
      $data['category_id'] = $category_popular_info['category_id'];
    } else {
      $data['category_id'] = 0;
    }

    if ($data['category_id'] != 0) {
      $this->load->model('catalog/category');
      $category_info = $this->model_catalog_category->getCategory((int)$data['category_id']);
      if ($category_info) {
        $data['category_name'] = $category_info['name'];
      }
    }

    if (isset($this->request->post['status'])) {
      $data['status'] = $this->request->post['status'];
    } elseif (!empty($category_popular_info)) {
      $data['status'] = $category_popular_info['status'];
    } else {
      $data['status'] = true;
    }

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/occategory_popular_form', $data));
  }

  protected function validate()
  {
        if (!$this->request->post['category_id']) {
            $this->error['category_id'] = $this->language->get('error_category');
        }

        return !$this->error;
  }

  public function install()
  {
    $this->load->model('extension/module/occategory_popular');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');
    $this->load->model('user/user_group');

    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/occategory_popular');
    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/occategory_popular');

    $this->model_extension_module_occategory_popular->install();
  }

  public function uninstall()
  {
    $this->load->model('extension/module/occategory_popular');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');
    $this->load->model('user/user_group');

    $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/module/occategory_popular');
    $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/module/occategory_popular');

    $this->model_extension_module_occategory_popular->uninstall();
    $this->model_setting_extension->uninstall('module_occategory_popular', $this->request->get['extension']);
    $this->model_setting_setting->deleteSetting($this->request->get['extension']);
  }
}
