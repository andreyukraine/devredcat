<?php

class ControllerStartupRedirect extends Controller
{
  private $file_cache = 'redirect';
  private $expire = 864000;
  private $my_cache = null;

  private $config;
  private $ocredirect_settings;
  private $request;
  private $db;

  public function __construct($registry)
  {
    parent::__construct($registry);
    $this->config = $this->registry->get('config');
    $this->db = $this->registry->get('db');
    $this->request = $this->registry->get('request');

    // Завантажуємо модель
    $this->load->model('extension/module/ocredirect');

    // Отримуємо налаштування
    $this->ocredirect_settings = $this->model_extension_module_ocredirect->getSettings();

    // Зберігаємо в реєстр для подальшого використання
    $registry->set('ocredirect_settings', $this->ocredirect_settings);
  }

  public function index()
  {
    if (!isset($this->ocredirect_settings['redirect_status']) || !$this->ocredirect_settings['redirect_status']) {
      return;
    }

    $request_uri = urldecode($this->request->server['REQUEST_URI']);
    $url = parse_url($request_uri);

    if ($this->request->server['HTTPS']) {
      $server = $this->config->get('config_ssl');
    } else {
      $server = $this->config->get('config_url');
    }

    $from_url = '';
    if (!empty($url['path'])) {
      $from_url = ltrim($url['path'], '/');
    }
    if (!empty($url['query'])) {
      //$from_url .= '?' . $url['query'];
    }
    $cache_data = $this->getCache();
    if (!$cache_data) {
      $sql = "SELECT from_url, to_url, code FROM `" . DB_PREFIX . "redirect` WHERE status = 1";
      $result = $this->db->query($sql);
      $cache_data = array();
      foreach ($result->rows as $row) {

        $fromUrl = $row['from_url'];
        $fromUrl = str_replace('.html', '', $fromUrl);

        $cache_data[$fromUrl] = array(
          'to_url' => $row['to_url'],
          'code' => $row['code'],
        );
      }
      $this->setCache($cache_data);
    }

    if (!empty($cache_data[$from_url])) {
      $to_url = $server . $cache_data[$from_url]['to_url'];
      $code = $cache_data[$from_url]['code'];
      $this->goRedirect(array(
        'code' => $code,
        'to_url' => $to_url,
        'from_url' => $from_url
      ));
    } else {
      foreach ($cache_data as $key_from_url => $to_code) {
        $pos_reg = strpos($key_from_url, '#');
        if ($pos_reg === 0) {
          if (preg_match($key_from_url, $from_url)) {
            $to_url = $server . $to_code['to_url'];
            $code = $to_code['code'];
            $this->goRedirect(array(
              'code' => $code,
              'to_url' => $to_url,
              'from_url' => $key_from_url
            ));
          }
        }
      }
    }
  }

  protected function goRedirect($data)
  {
    $this->addLog($data['from_url']);
    switch ($data['code']) {
      case 302:
      case 301:
      case 307: if ($data['to_url']) {
        header('Location: ' . str_replace('&amp;','&',$data['to_url']), true, $data['code']); exit;
      }
        break;
      case 404: return; break;
      case 410: header($_SERVER["SERVER_PROTOCOL"] . ' 410 Gone'); exit;
        //$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 410 Gone');
        break;
      case 403: header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden'); exit;
        break;
    }
  }

  protected function addLog($from_url)
  {
    $sql = "UPDATE " . DB_PREFIX . "redirect SET
		cnt = cnt + 1,
		last_date = NOW()
		WHERE from_url = '" . $this->db->escape($from_url) . "'";
    $this->db->query($sql);
  }

  protected function getCache()
  {
    if (empty($this->my_cache)) {
      $this->my_cache = new Cache($this->config->get('cache_engine'), $this->expire);
    }

    // Получаем текущий ID магазина
    $store_id = $this->config->get('config_store_id');

    // Формируем уникальный ключ для каждого магазина
    $cache_key = $this->file_cache . '_store_' . $store_id;

    return $this->my_cache->get($cache_key);
  }

  protected function setCache($data = array())
  {
    if (empty($this->my_cache)) {
      $this->my_cache = new Cache($this->config->get('cache_engine'), $this->expire);
    }

    // Получаем текущий ID магазина
    $store_id = $this->config->get('config_store_id');

    // Формируем уникальный ключ
    $cache_key = $this->file_cache . '_store_' . $store_id;

    if ($data) {
      $this->my_cache->set($cache_key, $data);
    }
  }

  public function getUrl()
  {
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $query = base64_decode($this->request->post['query']);
      $query = json_decode($query, true);
      if ($query) {
        $urls = [];

        $sql = "SELECT * FROM " . DB_PREFIX . "store";
        $results = $this->db->query($sql);
        $stores = [0 => [
          'ssl' => HTTPS_SERVER,
          'url' => HTTP_SERVER,
        ]
        ];
        foreach ($results->rows as $store) {
          $stores['store_id'] = [
            'ssl' => $store['ssl'],
            'url' => $store['url']
          ];
        }

        $sql = "SELECT * FROM " . DB_PREFIX . "language  where status=1";
        $results = $this->db->query($sql);
        if ($results->num_rows) {
          $languages = [];
          foreach ($results->rows as $row) {
            $languages[$row['language_id']] = $row['code'];
          }

          $servers['config_ssl'] = $this->config->get('config_ssl');
          $servers['config_url'] = $this->config->get('config_url');
          foreach ($stores as $store_id => $protocol) {
            $this->config->set('config_store_id', $store_id);
            $this->config->set('config_url', $protocol['url']);
            $this->config->set('config_ssl', $protocol['ssl']);
            foreach ($languages as $language_id => $code) {
              $this->config->set('config_language_id', $language_id);
              $this->config->set('config_language', $code);
              $this->session->data['language'] = $code;

              $url = $this->url->link($query);
              $urls[$store_id][$language_id] = $url;
            }

          }
          echo(base64_encode(json_encode($urls)));
          exit;
        }
      }
    }
  }

  public function redirect301_not_found(&$route, &$data)
  {
    $this->my_redirect();
  }

  public function redirect301(&$view, &$data, &$output)
  {
    $this->my_redirect();
  }

  private function my_redirect()
  {
    if (!$this->config->get('redirect_status')) return;

    $request_uri = urldecode($this->request->server['REQUEST_URI']);
    $url = parse_url($request_uri);

    if ($this->request->server['HTTPS']) {
      $server = $this->config->get('config_ssl');
    } else {
      $server = $this->config->get('config_url');
    }

    $from_url = '';
    if (!empty($url['path'])) {
      $from_url = str_replace('index.php', '', ltrim($url['path'], '/'));
    }
    if (!empty($url['query'])) {
      $from_url .= '?' . $url['query'];
    }
    $from_url_amp = str_replace('&amp;', '&', $from_url);

    $sql = "SELECT to_url, code, from_url FROM " . DB_PREFIX . "redirect 
		WHERE ('" . $this->db->escape($from_url) . "' = from_url
OR '" . $this->db->escape($from_url_amp) . "' = from_url)
		AND code <> 403 AND status=1 LIMIT 1";
    $query = $this->db->query($sql);
    $regexp = false;
    if (!$query->num_rows) {
      $sql = "SELECT to_url, code, from_url FROM " . DB_PREFIX . "redirect 
			WHERE '" . $this->db->escape($from_url) . "' REGEXP TRIM(BOTH '#' FROM from_url) AND code <> 403 AND status=1 LIMIT 1";
      $query = $this->db->query($sql);
      if ($query->num_rows) {
        $regexp = true;
      }
    }
    if ($query->num_rows) {
      if (preg_match('#^(http|https):\/\/#', $query->row['to_url'])) {
        $to_url = $query->row['to_url'];
      } else {
        if ($this->request->server['HTTPS']) {
          $server = $this->config->get('config_ssl');
        } else {
          $server = $this->config->get('config_url');
        }
        if ($regexp) {
          if (strpos($query->row['to_url'], '$') !== false) {

            $to_url = preg_replace('#' . $query->row['from_url'] . '#', str_replace(array('$1', '$2', '$3'), array('${1}', '${2}', '${3}'), $query->row['to_url']), $from_url);
          } else {
            $to_url = $query->row['to_url'];
          }
        } else {
          $to_url = $query->row['to_url'];
        }

        if ($to_url) {
          $to_url = $server . ltrim($to_url, '/');
        }
      }
      $code = $query->row['code'];
      $this->goRedirect([
        'code' => $code,
        'to_url' => $to_url,
        'from_url' => $query->row['from_url']
      ]);
    }
  }
}
