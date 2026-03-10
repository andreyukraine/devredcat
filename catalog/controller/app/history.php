<?php

class ControllerAppHistory extends Controller
{
  private $password = '777999'; // Замените на реальный пароль
  private $cache_file = DIR_CACHE . 'app_logs.json';

  public function index()
  {
    // Запускаем сессию, если она еще не начата
//    if (session_status() == PHP_SESSION_NONE) {
//      session_start();
//    }

    // Если пароль не введен или неверный, показываем форму
    if (!isset($_SESSION['auth_expire']) || $_SESSION['auth_expire'] < time()) {
      if (isset($this->request->post['password']) && $this->request->post['password'] === $this->password) {
        // Пароль верный, сохраняем время доступа (1 час)
        $_SESSION['auth_expire'] = time() + 1800; // 3600 сек = 1 час
      } else {
        // Показываем форму ввода пароля
        $data = array();

        $this->load->model('tool/image');
        $img_bg = "bg_login_app.jpeg";

        list($width_orig, $height_orig) = getimagesize(DIR_IMAGE . $img_bg);
        $height = (800 / $width_orig) * $height_orig;

        if (file_exists(DIR_IMAGE . $img_bg)) {
          $data["image"] = $this->model_tool_image->resize($img_bg, 1000, $height);
        } else {
          $data["image"] = $this->model_tool_image->resize("no_image.png", 1000, $height);
        }
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $this->response->setOutput($this->load->view('app/password_form', $data));
        return;
      }
    }

    $data = array();
    $data['logs'] = array();

    // Если доступ разрешен, загружаем основную страницу
    if (file_exists($this->cache_file)) {
      // Читаем данные из кэша
      $json = json_decode(file_get_contents($this->cache_file), true);
      $data['date_start'] = date('Y-m-d', strtotime(str_replace('.', '-', $json['date_start'])));
      $data['date_end'] = date('Y-m-d', strtotime(str_replace('.', '-', $json['date_end'])));
      $data['managers'] = !empty($json['logs']) ? array_values(array_unique(array_column($json['logs'], 'user'))) : [];

      // Сортируем логи по дате
      usort($json['logs'], function($a, $b) {
        return strtotime(str_replace('.', '-', $a['date'])) - strtotime(str_replace('.', '-', $b['date']));
      });

      $data['logs'] = !empty($json['logs']) ? $json['logs'] : [];
    }

    $data['logs_json'] = json_encode($data['logs'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

    if (isset($this->session->data['selected_manager'])) {
      $data['select_manager'] = $this->session->data['selected_manager'];
    }


    if (isset($this->request->get['ajax'])) {
      return $this->load->view('app/history', $data);
    } else {
      $data['footer'] = $this->load->controller('common/footer');
      $data['header'] = $this->load->controller('common/header');

      $this->response->setOutput($this->load->view('app/history', $data));
    }

  }

  public function change_manager()
  {
    $json = array();
    $json['status'] = false;
    $json['html'] = '';
    if (isset($this->request->post['selected_manager'])) {
      $json['status'] = true;
      $this->request->get['ajax'] = 1;
      $this->session->data['selected_manager'] = $this->request->post['selected_manager'];
      $json['html'] = $this->index();
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function get()
  {
    $json = array();
    $json['status'] = false;

    if (isset($this->request->post['date_start']) && isset($this->request->post['date_end'])) {
      $timestamp_start = strtotime($this->request->post['date_start']);
      $timestamp_end = strtotime($this->request->post['date_end']);

      $date_start = date('d-m-Y', $timestamp_start);
      $date_end = date('d-m-Y', $timestamp_end);

      $setting_module = $this->getSetting("module_ocimport");
      $login = $setting_module['login'];
      $password = $setting_module['password'];
      $authString = base64_encode("$login:$password");

      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://b2b.detta.com.ua/api/hs/site/app-story',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(array(
          'date_start' => $date_start,
          'date_end' => $date_end
        )),
        CURLOPT_HTTPHEADER => array(
          'Content-Type: application/json',
          'Authorization: Basic ' . $authString
        ),
      ));

      $response = curl_exec($curl);
      $error = curl_error($curl);
      curl_close($curl);

      if (!$error) {
        $json['status'] = true;
        $json = json_decode($response, true);

        // Сохраняем результат в кэш
        file_put_contents($this->cache_file, $response);
        $json['managers'] = !empty($json['logs']) ? array_values(array_unique(array_column($json['logs'], 'user'))) : [];

        $json['logs'] = !empty($json['logs']) ? $json['logs'] : [];

        // Сортируем логи по дате
        usort($json['logs'], function($a, $b) {
          return strtotime(str_replace('.', '-', $a['date'])) - strtotime(str_replace('.', '-', $b['date']));
        });

        $this->session->data['selected_manager'] = null;
        $this->request->get['ajax'] = 1;
        $json['html'] = $this->index();

      } else {
        $json = array('error' => true, 'message' => 'API request failed: ' . $error);
      }

    } else {
      $json = array('error' => true, 'message' => 'Missing date parameters');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function getSetting($code, $store_id = 0)
  {
    $setting_data = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

    foreach ($query->rows as $result) {
      if (!$result['serialized']) {
        $setting_data[$result['key']] = $result['value'];
      } else {
        $setting_data[$result['key']] = json_decode($result['value'], true);
      }
    }
    return $setting_data;
  }
}
