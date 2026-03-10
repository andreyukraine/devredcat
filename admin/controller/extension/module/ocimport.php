<?php

class ControllerExtensionModuleOcimport extends Controller
{
  private $error = array();
  private $status_file = DIR . 'crons/cron_status.txt';
  private $log_file = DIR . 'crons/cron_log.txt'; // Путь к вашему лог-файлу


  public function import_brand_img()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    // Явный вызов метода importDisconts
    $this->model_extension_module_ocimport->importBrandImg();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function ajaxUploadFile() {
    $json = array();

    if (!empty($this->request->files['file']['name'])) {
      // Handle file upload
      $upload_dir = DIR_UPLOAD; // Make sure this directory exists and is writable

      // Generate unique filename
      $filename = basename(html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8'));
      $filename = uniqid() . '_' . $filename;

      // Move uploaded file
      if (move_uploaded_file($this->request->files['file']['tmp_name'], $upload_dir . $filename)) {
        $json['filename'] = $filename;
      } else {
        $json['error'] = 'Could not upload file';
      }
    } else {
      $json['error'] = 'No file selected';
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function importMetaCategoryExcel() {
    $json = array();

    $this->load->model('extension/module/ocimport');

    if (!empty($this->request->post['import_meta'])) {
      $filename = $this->request->post['import_meta'];
      $upload_dir = DIR_UPLOAD;
      $filepath = $upload_dir . $filename;

      if (file_exists($filepath)) {
        // Call your model to process the Excel
        $result = $this->model_extension_module_ocimport->importMetaCategoryExcel($filepath);

        if ($result) {
          $json['success'] = "Імпортовано meta з ексель";
          // Optionally delete the file after processing
          // unlink($filepath);
        } else {
          $json['error'] = "Помилка при імпорті даних";
        }
      } else {
        $json['error'] = "Файл не знайдено";
      }
    } else {
      $json['error'] = "Виберіть файл імпорту";
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function import_product_img()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    // Явный вызов метода importDisconts
    $this->model_extension_module_ocimport->importProductImg();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function crop_product_img()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    $img_name = "";
    if (isset($this->request->get['img_name'])) { // Перевіряємо, чи прийшов img_name
      $img_name = $this->request->get['img_name'];
    }

    $filter_data = array();
    if (isset($this->request->get['img_category'])) { // Перевіряємо, чи прийшов img_category
      $filter_data = array(
        'filter_category'		=> $this->request->get['img_category']
      );
    }

    // Явный вызов метода importDisconts
    $this->model_extension_module_ocimport->cropProductImg($filter_data, $img_name);

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function update_groupe()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    // Явный вызов метода importDisconts
    $this->model_extension_module_ocimport->importGroupe();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function update_price_groups_client()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    // Явный вызов метода importDisconts
    $this->model_extension_module_ocimport->importPriceGroupsClient();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function update_addresses_client()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    // Явный вызов метода importDisconts
    $this->model_extension_module_ocimport->importAddressesClient();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function update_filter()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    // Явный вызов метода importDisconts
    $this->model_extension_module_ocimport->updateFilter();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function import_category_img()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    // Явный вызов метода importDisconts
    $this->model_extension_module_ocimport->importCategoryImg();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function import_disconts()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    // Явный вызов метода importDisconts
    $this->model_extension_module_ocimport->importDisconts();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function import_cards()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    // Явный вызов метода importCards
    $this->model_extension_module_ocimport->importCards();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function import_products()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    // Явный вызов метода importCards
    $this->model_extension_module_ocimport->importProducts();

    $this->model_extension_module_ocimport->updateFilter();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function clear_cards()
  {

    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    $this->model_extension_module_ocimport->clearCards();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));

  }

  public function clear_categories()
  {

    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    $this->model_extension_module_ocimport->clearCategories();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function clear_warehouse()
  {

    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    $this->model_extension_module_ocimport->clearWarehouses();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function clear_products()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    $this->model_extension_module_ocimport->clearProducts();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function clear_addresses_client()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    $this->model_extension_module_ocimport->clearAddressesClient();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function clear_customer_address_price_groups()
  {
    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    $this->load->model('extension/module/ocimport');

    $this->model_extension_module_ocimport->clearCustomerAddressPriceGroups();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function cron_prods()
  {
    // Определяем путь к PHP в зависимости от окружения
    if ($_SERVER['HTTP_HOST'] == "b2bdetta") {
      $php_path = "/opt/homebrew/bin/php";  // локальная разработка
    } else {
      // Для продакшена используем абсолютный путь
      // Проверьте правильный путь на вашем сервере:
      // ssh root@server "which php" или "whereis php"
      $php_path = "/opt/php74/bin/php";  // стандартный путь на многих серверах
      // или $php_path = "/usr/local/bin/php";
      // или $php_path = "/opt/alt/php81/usr/bin/php";
    }

    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    // Проверяем размер лог-файла
    if (file_exists($this->log_file)) {
      $max_size = 104857600; // 100 МБ

      if (filesize($this->log_file) > $max_size) {
        unlink($this->log_file);
      }
    }

    // Формируем команду
    $command = $php_path . ' ' . DIR . 'crons/cron_prods.php >> ' . $this->log_file . ' 2>&1 &';

    // Проверяем доступность shell_exec
    if (!function_exists('shell_exec')) {
      $json['status'] = 'Помилка: shell_exec відключено на сервері';
      $json['command'] = $command;
    } else {
      // Запускаем и проверяем результат
      $output = shell_exec($command);

      if ($output === null) {
        // Команда выполняется в фоне, но можно проверить по-другому
        $json['debug'] = 'Команда відправлена в фоновий режим';
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function cron_cards()
  {

    // Получаем путь к PHP
    if ($_SERVER['HTTP_HOST'] == "b2bdetta") {
      $php_path = "/opt/homebrew/bin/php";
    } else {
      $php_path = exec('which php');
    }

    $json['status'] = 'Процес виконується.';
    file_put_contents($this->status_file, "started");

    // Запускаем cron-задачу асинхронно через shell_exec
    $command = $php_path . ' ' . DIR . 'crons/cron_cards.php >> ' . $this->log_file . ' 2>&1 &';

    //print_r($command);

    shell_exec($command);

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));

  }


  public function checkCronStatus()
  {

    if (file_exists($this->status_file)) {
      $lines = file($this->status_file, FILE_IGNORE_NEW_LINES);
      $status = array_shift($lines);

      switch ($status) {
        case "started":
          $json['status'] = 'Задача все ще виконується...';
          break;
        case "load_products":
          $json['status'] = 'Завантажуємо товари...';
          break;
        case "load":
          $json['status'] = 'Завантажуємо ...';
          break;
        case "completed":
          $json['success'] = 'Задачу завершено!';
          break;
        case "error":
          $json['error'] = $lines;
          break;
        default:
          $json['status'] = 'Процес виконується.';
          break;
      }

      if (!empty($lines)) {
        $json['progress'] = implode("\n", $lines);
      }

    } else {
      $json['error'] = 'Файл статусу не знайдено.';
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function email(){
    if (!empty($this->request->post["mail_from"])) {

      $this->load->language('mail/register');

      $data['text_login'] = $this->language->get('text_login');
      $data['text_approval'] = $this->language->get('text_approval');
      $data['text_service'] = $this->language->get('text_service');
      $data['text_thanks'] = $this->language->get('text_thanks');

      $this->load->model('customer/customer_group');

      if (isset($args[0]['customer_group_id'])) {
        $customer_group_id = $args[0]['customer_group_id'];
      } else {
        $customer_group_id = $this->config->get('config_customer_group_id');
      }

      $customer_group_info = $this->model_customer_customer_group->getCustomerGroup($customer_group_id);

      if ($customer_group_info) {
        $data['approval'] = $customer_group_info['approval'];
      } else {
        $data['approval'] = '';
      }

      $data['t_data'] = $this->load->controller('mail/template_data');

      $mail = new Mail();
      $mail->protocol = $this->config->get('config_mail_engine');
      $mail->parameter = $this->config->get('config_mail_parameter');
      $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
      $mail->smtp_username = $this->config->get('config_mail_smtp_username');
      $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
      $mail->smtp_port = $this->config->get('config_mail_smtp_port');
      $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

      $mail->setTo($this->request->post["mail_from"]);
      $mail->setFrom($this->config->get('config_email'));
      $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
      $mail->setSubject("test subject");
      $mail->setHtml($this->load->view('mail/register', $data));
      try {
        $mail->send();
        $this->log->write('Email sent to: ' . $this->request->post["mail_from"]);
      } catch (Exception $e) {
        $this->log->write('Email send error: ' . $e->getMessage());
      }
    }

    $data['user_token'] = $this->session->data['user_token'];

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/ocimport', $data));
  }

  public function index()
  {

    $this->load->language('extension/module/ocimport');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('setting/setting');
    $this->load->model('setting/module');


    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

      $this->model_setting_setting->editSetting("module_ocimport", $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link('extension/module/ocimport', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
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
      'href' => $this->url->link('extension/module/ocimport', 'user_token=' . $this->session->data['user_token'], true),
    );


    $data['action'] = $this->url->link('extension/module/ocimport', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    if ($this->request->server['REQUEST_METHOD'] != 'POST') {
      $module_info = $this->model_setting_setting->getSetting("module_ocimport");
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

    if (isset($this->request->post['url'])) {
      $data['url'] = $this->request->post['url'];
    } elseif (!empty($module_info)) {
      $data['url'] = $module_info['url'];
    } else {
      $data['url'] = '';
    }

    if (isset($this->request->post['login'])) {
      $data['login'] = $this->request->post['login'];
    } elseif (!empty($module_info)) {
      $data['login'] = $module_info['login'];
    } else {
      $data['login'] = '';
    }

    if (isset($this->request->post['password'])) {
      $data['password'] = $this->request->post['password'];
    } elseif (!empty($module_info)) {
      $data['password'] = $module_info['password'];
    } else {
      $data['password'] = '';
    }

    //------------- error -----------
    if (isset($this->error['url'])) {
      $data['error_url'] = $this->error['url'];
    } else {
      $data['error_url'] = '';
    }

    if (isset($this->error['login'])) {
      $data['error_login'] = $this->error['login'];
    } else {
      $data['error_login'] = '';
    }

    if (isset($this->error['password'])) {
      $data['error_password'] = $this->error['password'];
    } else {
      $data['error_password'] = '';
    }

    if (isset($this->error['name'])) {
      $data['error_name'] = $this->error['name'];
    } else {
      $data['error_name'] = '';
    }

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/ocimport_setting', $data));
  }

  public function import()
  {
    $this->load->language('extension/module/ocimport');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/ocimport');
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
      'href' => $this->url->link('extension/module/ocimport', 'user_token=' . $this->session->data['user_token'], true),
    );

    $data['email'] = $this->url->link('extension/module/ocimport/email', 'user_token=' . $this->session->data['user_token'], true);

    $data['action'] = $this->url->link('extension/module/ocimport/import', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
      $data['action'] = $this->url->link('extension/module/ocimport/import', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
    }
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $this->model_extension_module_ocimport->importing($this->request);
    }


    if (isset($this->error['name'])) {
      $data['error_name'] = $this->error['name'];
    } else {
      $data['error_name'] = '';
    }

    // Categories
    $this->load->model('catalog/category');

    $categories = $this->model_catalog_category->getAllCategories();
    $data['product_categories'] = array();

    foreach ($categories as $category) {
      $category_info = $this->model_catalog_category->getCategory($category['category_id']);

      if ($category_info) {
        $data['product_categories'][] = array(
          'category_id' => $category_info['category_id'],
          'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
        );
      }
    }


    $data['user_token'] = $this->session->data['user_token'];

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/ocimport', $data));
  }

  protected function validate()
  {
    if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
      $this->error['name'] = $this->language->get('error_name');
    }

    if (empty($this->request->post['url'])) {
      $this->error['url'] = $this->language->get('error_url');
    }

    if (empty($this->request->post['login'])) {
      $this->error['login'] = $this->language->get('error_login');
    }

    if (empty($this->request->post['password'])) {
      $this->error['password'] = $this->language->get('error_password');
    }

    return !$this->error;
  }

  public function install()
  {
    $this->load->model('extension/module/ocimport');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');

    $this->model_extension_module_ocimport->install();
//
//    $data = array(
//      'module_ocimport_article_limit' => '10',
//      'module_ocimport_meta_title' => 'Import',
//    );
//    $this->model_setting_setting->editSetting('module_ocimport', $data, 0);

    $this->model_setting_setting->editSetting('module_ocimport', [
      'status' => '1',
      'url' => '',
      'login' => '',
      'password' => '',
    ]);


  }


  public function uninstall()
  {
    $this->load->model('extension/module/ocimport');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');

    $this->model_extension_module_ocimport->uninstall();
    $this->model_setting_extension->uninstall('module_ocimport', $this->request->get['extension']);
    $this->model_setting_setting->deleteSetting($this->request->get['extension']);
  }
}
