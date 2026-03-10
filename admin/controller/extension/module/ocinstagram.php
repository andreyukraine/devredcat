<?php

class ControllerExtensionModuleOcinstagram extends Controller
{
  private $error = array();

  public function install()
  {
    $this->load->model('extension/module/ocinstagram');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');

    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/ocinstagram');
    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/ocinstagram');

    $this->model_extension_module_ocinstagram->install();

    $data = array(
      'module_ocinstagram_status' => true,
      'module_ocinstagram_title' => 'Ocinstagram',
    );

    $this->model_setting_setting->editSetting('module_ocinstagram', $data, 0);
  }

  public function uninstall()
  {
    $this->load->model('extension/module/ocinstagram');
    $this->load->model('setting/setting');
    $this->load->model('setting/extension');

    $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/module/ocinstagram');
    $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/module/ocinstagram');

    $this->model_extension_module_ocinstagram->uninstall();
    $this->model_setting_extension->uninstall('module_ocinstagram', $this->request->get['extension']);
    $this->model_setting_setting->deleteSetting($this->request->get['extension']);
  }

  public function index()
  {
    $this->load->language('extension/module/ocinstagram');

    $this->document->setTitle($this->language->get('page_title'));

    $this->load->model('extension/module/ocinstagram');
    $this->load->model('setting/setting');

    $data['user_token'] = $this->session->data['user_token'];

    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

      $this->model_setting_setting->editSetting("ocinstagram", $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link('extension/module/ocinstagram', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
    }

    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    if (isset($this->error['access_token'])) {
      $data['error_access_token'] = $this->error['access_token'];
    } else {
      $data['error_access_token'] = '';
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

    if (!isset($this->request->get['module_id'])) {
      $data['breadcrumbs'][] = array(
        'text' => $this->language->get('heading_title'),
        'href' => $this->url->link('extension/module/ocinstagram', 'user_token=' . $this->session->data['user_token'], true)
      );
    } else {
      $data['breadcrumbs'][] = array(
        'text' => $this->language->get('heading_title'),
        'href' => $this->url->link('extension/module/ocinstagram', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
      );
    }

    if (!isset($this->request->get['module_id'])) {
      $data['action'] = $this->url->link('extension/module/ocinstagram', 'user_token=' . $this->session->data['user_token'], true);
    } else {
      $data['action'] = $this->url->link('extension/module/ocinstagram', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
    }

    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    if ($this->request->server['REQUEST_METHOD'] != 'POST') {
      $module_info = $this->model_setting_setting->getSetting("ocinstagram");
    }

    if (isset($this->request->post['ocinstagram_status'])) {
      $data['ocinstagram_status'] = $this->request->post['ocinstagram_status'];
    } elseif (!empty($module_info)) {
      $data['ocinstagram_status'] = $module_info['ocinstagram_status'];
    } else {
      $data['ocinstagram_status'] = 1;
    }

    if (isset($this->request->post['ocinstagram_access_token'])) {
      $data['ocinstagram_access_token'] = $this->request->post['ocinstagram_access_token'];
    } elseif (!empty($module_info)) {
      $data['ocinstagram_access_token'] = $module_info['ocinstagram_access_token'];
    } else {
      $data['ocinstagram_access_token'] = '';
    }

    $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
    $limit = 30; // Указываем лимит элементов на странице

    // Рассчитываем начало выборки
    $start = ($page - 1) * $limit;

    // Получаем общее количество карт
    $total = $this->model_extension_module_ocinstagram->getTotalPosts();

    $data['posts'] = $this->model_extension_module_ocinstagram->getPostList($start, $limit);

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/ocinstagram', $data));
  }


  public function get_posts()
  {
    $json["status"] = true;

    $this->load->model('setting/setting');
    $this->load->model('extension/module/ocinstagram');

    $setting = $this->model_setting_setting->getSetting("ocinstagram");

    $url = "https://graph.instagram.com/v18.0/me/media?fields=id,caption,media_url,media_type,permalink,thumbnail_url,timestamp&access_token=". $setting['ocinstagram_access_token'];
    $content = @file_get_contents($url);

    if ($content === false) {
      $json["status"] = false;
    } else {
      $instagramJsonData = json_decode($content, true);

      if (isset($instagramJsonData['data'])) {

        $this->model_extension_module_ocinstagram->clearPosts();

        // Определяем язык в зависимости от кода
        $locale = 'uk_UA'; // По умолчанию украинский
        switch ($this->language->get('code')) {
          case "en":
            $locale = 'en_US';
            break;
          case "ru":
            $locale = 'ru_RU';
            break;
          case "uk":
            $locale = 'uk_UA';
            break;
        }

        // Используем IntlDateFormatter для форматирования даты
        $dateFormatter = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'MMM');

        foreach ($instagramJsonData['data'] as $key => $instagramData) {

          $date_added_m = $dateFormatter->format(new \DateTime(date('m/d/Y', strtotime($instagramData['timestamp']))));

          $img = $instagramData['media_url'];
          if ($instagramData['media_type'] == "VIDEO"){
            $img = $instagramData['thumbnail_url'];
          }

          $img_storege = $this->downloadInstagramImage($img);

          $data = array(
            'title' => $instagramData['caption'] ? (utf8_strlen($instagramData['caption']) > 120 ? utf8_substr($instagramData['caption'], 0, 60) . '..' : $instagramData['caption']) : '',
            'caption' => $instagramData['caption'] ? : '',
            'image' => $img_storege,
            'link' => $instagramData['permalink'],
            'created_time' => date('m/d/Y', strtotime($instagramData['timestamp'])),
            'date_added' => date($this->language->get('date_format_short'), strtotime(strtotime($instagramData['timestamp']))),
            'date_added_m' => ucfirst($date_added_m),
            'date_added_d' => date("d", strtotime($instagramData['timestamp'])),
            'date_added_y' => date("Y", strtotime($instagramData['timestamp'])),
          );

          $this->model_extension_module_ocinstagram->addPost($data);

        }
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  function downloadInstagramImage($postUrl, $savePath = 'catalog/instagram/') {
    // Отримуємо тимчасовий URL (можна використати попередній метод)

    if (!$postUrl) return false;

    // Повний шлях до папки
    $fullPath = DIR_IMAGE . $savePath;

    // Перевіряємо чи існує папка, якщо ні - створюємо
    if (!file_exists($fullPath)) {
      if (!mkdir($fullPath, 0755, true)) {
        // Якщо не вдалося створити папку
        return false;
      }
    }

    // Генеруємо унікальне ім'я файлу
    $filename = md5($postUrl) . '.jpg';
    $fullFilePath = $fullPath . $filename;

    // Завантажуємо зображення
    $imageData = file_get_contents($postUrl);
    if ($imageData === false) {
      return false;
    }

    // Зберігаємо файл
    $result = file_put_contents($fullFilePath, $imageData);
    if ($result === false) {
      return false;
    }

    return $savePath . $filename;
  }

  protected function validate()
  {
    if (!$this->user->hasPermission('modify', 'extension/module/ocinstagram')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    if (!$this->request->post['access_token']) {
      $this->error['access_token'] = $this->language->get('error_access_token');
    }

    return !$this->error;
  }
}
