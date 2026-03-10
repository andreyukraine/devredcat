<?php

class Controllerproductspecial extends Controller
{
  public function index()
  {
    $this->load->language('product/discont');

    $this->load->model('catalog/product');
    $this->load->model('catalog/product_status');
    $this->load->model('tool/image');
    $this->load->model('account/customer');

    $width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width');
    $height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height');
    if ($this->mobile_detect->isMobile()) {
      $width = 150;
      $height = 150;
    }

    //режим каталогу
    if ($this->config->get('config_is_catalog')) {
      $data['config_is_catalog'] = $this->config->get('config_is_catalog');
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
      'href' => $this->url->link('product/disconts', $url)
    );

    $data['products'] = array();

    //визначаємо групу користувача
    if ($this->customer->isLogged()) {
      $customer_group_id = $this->customer->getGroupId();
    } else {
      $customer_group_id = $this->config->get('config_customer_group_id');
    }

    $filter_data = array(
      'sort' => $sort,
      'order' => $order,
      'filter' => isset($this->request->get['filter']) ? $this->request->get['filter'] : '',
      'start' => ($page - 1) * $limit,
      'limit' => $limit,
      'customer_group_id' => $customer_group_id
    );

    if (isset($this->request->post['category_id'])) {
      $filter_data['filter_category_id'] = $this->request->post['category_id'];
    }

    $results = $this->model_catalog_product->getProductsDiscont($filter_data);
    $product_total = $results['total_products'];;

    $mass_prod_id = array();

    if (isset($results['products'])) {
      foreach ($results['products'] as $result) {

        if (!in_array($result['product_id'], $mass_prod_id)) {
          $mass_prod_id[] = $result['product_id'];
        }

        $sort_order = "ASC";
        if (isset($this->request->get['order'])) {
          $sort_order = $this->request->get['order'];
        }

        $product_data = array(
          'item' => $result,
          'sort_order' => $sort_order
        );
        $options = $this->load->controller('product/options', $product_data);

        $data['products'][] = array(
          'product_id' => $result['product_id'],
          'images' => $options['images'],
          'options' => $options['prod_options'],
          'name' => (utf8_strlen($result['name']) > 60 ? utf8_substr($result['name'], 0, 60) . '..' : $result['name']),
          'price' => $this->currency->format($options['price'], $this->session->data['currency']),
          'special' => $options['special'] > 0 ? $this->currency->format($options['special'], $this->session->data['currency']) : false,
          'rate_special' => $options['percent'],
          'is_buy' => $options['is_buy'],
          'quantity' => $options['quantity'],
          'rating' => (int)$result['rating'],
          'uniq_id' => $options['uniq_id'],
          'cart_id' => $options['cart_id'],
          'in_cart' => $options['in_cart'],
          'in_wishlist' => $options['in_wishlist'] ? 1 : 0,
          'statuses' => $options['statuses'],
          'on_stock' => $options['on_stock'],
          'date_end' => $options['date_end'],
          'href' => $this->url->link('product/product', 'product_id=' . $result['product_id'] . $url)
        );
      }
    }

    //получаем категории товаров
    if (!isset($this->request->get['ajax'])) {
      if (!empty($mass_prod_id)) {
        $data['category_tree'] = $this->load->controller('product/category_tree', $mass_prod_id);
      }
    }

    $url = '';

    if (isset($this->request->get['limit'])) {
      $url .= '&limit=' . $this->request->get['limit'];
    }

    $data['sorts'] = array();

    $data['sorts'][] = array(
      'text' => $this->language->get('text_default'),
      'value' => 'p.sort_order-ASC',
      'href' => $this->url->link('product/discont', 'sort=p.sort_order&order=ASC' . $url)
    );

    $data['sorts'][] = array(
      'text' => $this->language->get('text_name_asc'),
      'value' => 'pd.name-ASC',
      'href' => $this->url->link('product/discont', 'sort=pd.name&order=ASC' . $url)
    );

    $data['sorts'][] = array(
      'text' => $this->language->get('text_name_desc'),
      'value' => 'pd.name-DESC',
      'href' => $this->url->link('product/discont', 'sort=pd.name&order=DESC' . $url)
    );

    $data['sorts'][] = array(
      'text' => $this->language->get('text_price_asc'),
      'value' => 'ps.price-ASC',
      'href' => $this->url->link('product/discont', 'sort=ps.price&order=ASC' . $url)
    );

    $data['sorts'][] = array(
      'text' => $this->language->get('text_price_desc'),
      'value' => 'ps.price-DESC',
      'href' => $this->url->link('product/discont', 'sort=ps.price&order=DESC' . $url)
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
        'href' => $this->url->link('product/discont', $url . '&limit=' . $value)
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
    $pagination->total = $product_total;
    $pagination->page = $page;
    $pagination->limit = $limit;
    $pagination->url = $this->url->link('product/discont', $url . '&page={page}');

    $data['pagination'] = $pagination->render();
    $data['page'] = $page;
    $data['product_total'] = count($data['products']);
    $data['total_pages'] = ceil($product_total / $limit);

    $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

    if ($page == 2) {
      $this->document->addLink($this->url->link('product/discont', ''), 'prev');
    } elseif ($page > 2) {
      $this->document->addLink($this->url->link('product/discont', 'page=' . ($page - 1)), 'prev');
    }
    if ($limit && ceil($product_total / $limit) > $page) {
      $this->document->addLink($this->url->link('product/discont', 'page=' . ($page + 1)), 'next');
    }

    $canonical_url = $this->url->link('product/discont', '');
    $this->document->addLink($canonical_url, 'canonical');

    if ($page > 1){
      $data['heading_title'] = $data['heading_title'] . " | сторінка " . $page;
      $this->document->setTitle($this->document->getTitle() . " | сторінка " . $page);
    }

    $data['sort'] = $sort;
    $data['order'] = $order;
    $data['limit'] = $limit;

    $data['continue'] = $this->url->link('common/home');

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    if (isset($this->request->get['ajax'])) {
      return $this->load->view('product/category_ajax', $data);
    } else {
      $this->response->setOutput($this->load->view('product/discont', $data));
    }
  }

}
