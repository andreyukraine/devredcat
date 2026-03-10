<?php

class ControllerProductAkcii
  extends Controller
{
  public function index()
  {
    $this->load->language('product/akcii');

    $this->load->model('catalog/akcii');
    $this->load->model('tool/image');

    $data['width'] = 300;
    $data['height'] = 400;
    if ($this->mobile_detect->isMobile()) {
      $data['width'] = 150;
      $data['height'] = 200;
    }

    if ($this->config->get('config_noindex_disallow_params')) {
      $params = explode("\r\n", $this->config->get('config_noindex_disallow_params'));
      if (!empty($params)) {
        $disallow_params = $params;
      }
    }

    if (isset($this->request->get['sort'])) {
      $sort = $this->request->get['sort'];
      if (!in_array('sort', $disallow_params, true) && $this->config->get('config_noindex_status')) {
        $this->document->setRobots('noindex,follow');
      }
    } else {
      $sort = 'p.sort_order';
    }

    if (isset($this->request->get['order'])) {
      $order = $this->request->get['order'];
      if (!in_array('order', $disallow_params, true) && $this->config->get('config_noindex_status')) {
        $this->document->setRobots('noindex,follow');
      }
    } else {
      $order = 'ASC';
    }

    if (isset($this->request->get['page'])) {
      $page = (int)$this->request->get['page'];
      if (!in_array('page', $disallow_params, true) && $this->config->get('config_noindex_status')) {
        $this->document->setRobots('noindex,follow');
      }
    } else {
      $page = 1;
    }

    if (isset($this->request->get['limit'])) {
      $limit = (int)$this->request->get['limit'];
      if (!in_array('limit', $disallow_params, true) && $this->config->get('config_noindex_status')) {
        $this->document->setRobots('noindex,follow');
      }
    } else {
      $limit = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
    }

    $this->document->setTitle($this->language->get('heading_title'));

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

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

    if (isset($this->request->get['limit'])) {
      $url .= '&limit=' . $this->request->get['limit'];
    }

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('product/akcii', $url)
    );

    $data['akcii'] = array();

    $filter_data = array(
      'sort' => $sort,
      'order' => $order,
      'start' => ($page - 1) * $limit,
      'limit' => $limit
    );

    $akcii_total = $this->model_catalog_akcii->getTotalAkcii($filter_data);
    $results = $this->model_catalog_akcii->getAkcii($filter_data);

    foreach ($results as $result) {


      if (isset($result['image']) && file_exists(DIR_IMAGE . $result['image'])) {
        $image = $this->model_tool_image->resize($result['image'], $data['width'], $data['height']);
      } else {
        $image = $this->model_tool_image->resize("no_image.png", $data['width'], $data['height']);
      }

      $currentDate = new DateTime(); // Текущая дата и время
      $endDate = new DateTime($result['date_end']); // Дата окончания акции

      // Акция еще активна
      $is_expired = false;

      if ($endDate < $currentDate) {
        // Акция уже завершилась
        $is_expired = true;
      }


      $data['akcii'][] = array(
        'akcia_id' => $result['akcia_id'],
        'image' => $image,
        'width' => $data['width'],
        'height' => $data['height'],
        'is_expired' => $is_expired,
        'name' => (utf8_strlen($result['name']) > 60 ? utf8_substr($result['name'], 0, 60) . '..' : $result['name']),
        'desc' => utf8_substr(trim(strip_tags(html_entity_decode($result['desc'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
        'date_start' => $result['date_start'],
        'date_end' => $result['date_end'],
        'href' => $this->url->link('product/akcii', 'akcia_id=' . $result['akcia_id'] . $url)
      );
    }

    $url = '';

    if (isset($this->request->get['limit'])) {
      $url .= '&limit=' . $this->request->get['limit'];
    }

    $data['sorts'] = array();

    $data['sorts'][] = array(
      'text' => $this->language->get('text_default'),
      'value' => 'p.sort_order-ASC',
      'href' => $this->url->link('product/akcii', 'sort=p.sort_order&order=ASC' . $url)
    );

    $data['sorts'][] = array(
      'text' => $this->language->get('text_name_asc'),
      'value' => 'pd.name-ASC',
      'href' => $this->url->link('product/akcii', 'sort=pd.name&order=ASC' . $url)
    );

    $data['sorts'][] = array(
      'text' => $this->language->get('text_name_desc'),
      'value' => 'pd.name-DESC',
      'href' => $this->url->link('product/akcii', 'sort=pd.name&order=DESC' . $url)
    );

    $url = '';

    if (isset($this->request->get['sort'])) {
      $url .= '&sort=' . $this->request->get['sort'];
    }

    if (isset($this->request->get['order'])) {
      $url .= '&order=' . $this->request->get['order'];
    }

    $data['limits'] = array();

    $limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

    sort($limits);

    foreach ($limits as $value) {
      $data['limits'][] = array(
        'text' => $value,
        'value' => $value,
        'href' => $this->url->link('product/akcii', $url . '&limit=' . $value)
      );
    }

    $url = '';

    if (isset($this->request->get['sort'])) {
      $url .= '&sort=' . $this->request->get['sort'];
    }

    if (isset($this->request->get['order'])) {
      $url .= '&order=' . $this->request->get['order'];
    }

    if (isset($this->request->get['limit'])) {
      $url .= '&limit=' . (int)$this->request->get['limit'];
    }

    $pagination = new Pagination();
    $pagination->total = $akcii_total;
    $pagination->page = $page;
    $pagination->limit = $limit;
    $pagination->url = $this->url->link('product/akcii', $url . '&page={page}');

    $data['pagination'] = $pagination->render();
    $data['page'] = $page;
    $data['akcii_total'] = count($data['akcii']);
    $data['total_pages'] = ceil($akcii_total / $limit);

    $data['results'] = sprintf($this->language->get('text_pagination'), ($akcii_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($akcii_total - $limit)) ? $akcii_total : ((($page - 1) * $limit) + $limit), $akcii_total, ceil($akcii_total / $limit));

    if ($page == 2) {
      $this->document->addLink($this->url->link('product/akcii', ''), 'prev');
    } elseif ($page > 2) {
      $this->document->addLink($this->url->link('product/akcii', 'page=' . ($page - 1)), 'prev');
    }
    if ($limit && ceil($akcii_total / $limit) > $page) {
      $this->document->addLink($this->url->link('product/akcii', 'page=' . ($page + 1)), 'next');
    }

    $canonical_url = $this->url->link('product/akcii', '');
    $this->document->addLink($canonical_url, 'canonical');

    $data['sort'] = $sort;
    $data['order'] = $order;
    $data['limit'] = $limit;

    $data['continue'] = $this->url->link('common/home');

    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->response->setOutput($this->load->view('product/akcii', $data));

  }


  public function info()
  {
    $this->load->language('product/akcii');

    $this->load->model('catalog/akcii');

    $this->load->model('tool/image');

    $data['width'] = 600;
    $data['height'] = 800;
    if ($this->mobile_detect->isMobile()) {
      $data['width'] = 300;
      $data['height'] = 400;
    }


    if (isset($this->request->get['akcia_id'])) {
      $akcia_id = (int)$this->request->get['akcia_id'];
      $data['akcia_id'] = $akcia_id;
    } else {
      $akcia_id = 0;
      $data['akcia_id'] = 0;
    }

    if ($this->config->get('config_noindex_disallow_params')) {
      $params = explode("\r\n", $this->config->get('config_noindex_disallow_params'));
      if (!empty($params)) {
        $disallow_params = $params;
      }
    }

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('product/akcii')
    );

    $data['text_desc_title'] = $this->language->get('text_desc_title');
    $data['text_termin_title'] = $this->language->get('text_termin_title');
    $data['text_termin_start'] = $this->language->get('text_termin_start');
    $data['text_termin_end'] = $this->language->get('text_termin_end');
    $data['text_akcia_end'] = $this->language->get('text_akcia_end');

    $data['find'] = false;

    $akcia_info = $this->model_catalog_akcii->getAkcia($akcia_id);

    if ($akcia_info) {

      $data['find'] = true;

      if ($akcia_info['meta_title']) {
        $this->document->setTitle($akcia_info['meta_title']);
      } else {
        $this->document->setTitle($akcia_info['name']);
      }

      if ($akcia_info['meta_h1']) {
        $data['heading_title'] = $akcia_info['meta_h1'];
      } else {
        $data['heading_title'] = $akcia_info['name'];
      }

      $this->document->setDescription($akcia_info['meta_description']);
      $this->document->setKeywords($akcia_info['meta_keyword']);

      $data['name'] = html_entity_decode($akcia_info['name'], ENT_QUOTES, 'UTF-8');
      $data['desc'] = html_entity_decode($akcia_info['desc'], ENT_QUOTES, 'UTF-8');

      $data['url'] = $akcia_info['url'];

      $data['date_start'] = date('d-m-Y', strtotime($akcia_info['date_start']));
      $data['date_end'] = date('d-m-Y', strtotime($akcia_info['date_end']));

      $currentDate = new DateTime(); // Текущая дата и время
      $endDate = new DateTime($akcia_info['date_end']); // Дата окончания акции

      // Акция еще активна
      $data['is_expired'] = false;

      if ($endDate < $currentDate) {
        // Акция уже завершилась
        $data['is_expired'] = true;
      }

      if (isset($akcia_info['image']) && file_exists(DIR_IMAGE . $akcia_info['image'])) {
        $data['image'] = $this->model_tool_image->resize($akcia_info['image'], $data['width'], $data['height']);
      } else {
        $data['image'] = $this->model_tool_image->resize("no_image.png", $data['width'], $data['height']);
      }

      $data['breadcrumbs'][] = array(
        'text' => $akcia_info['name'],
        'href' => $this->url->link('product/akcii/info', 'akcia_id=' . $this->request->get['akcia_id'])
      );

      $data['continue'] = $this->url->link('common/home');

      $data['footer'] = $this->load->controller('common/footer');
      $data['header'] = $this->load->controller('common/header');

      $this->response->setOutput($this->load->view('product/akcii_info', $data));

    } else {

      $this->document->setTitle($this->language->get('text_error'));

      $data['heading_title'] = $this->language->get('text_error');

      $data['text_error'] = $this->language->get('text_error');

      $data['continue'] = $this->url->link('common/home');

      $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

      $data['header'] = $this->load->controller('common/header');
      $data['footer'] = $this->load->controller('common/footer');

      $this->response->setOutput($this->load->view('error/not_found', $data));
    }
  }
}
