<?php

class ControllerProductManufacturer extends Controller
{
  public function index()
  {

    $this->load->language('product/manufacturer');
    $this->load->model('catalog/manufacturer');
    $this->load->model('tool/image');
    $this->load->model('catalog/product');

    $this->document->setTitle($this->language->get('heading_title'));

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('product/manufacturer')
    );

    $data['manufacturers'] = array();

    $width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width');
    $height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height');
    if ($this->mobile_detect->isMobile()) {
      $width = 150;
      $height = 150;
    }

    $filter = array();
    $results = $this->model_catalog_manufacturer->getManufacturers($filter);
    if (!empty($results)) {

      foreach ($results as $result) {

        $filter_data = array(
          'filter_manufacturer_id' => $result['manufacturer_id'],
        );
        $results_prods = $this->model_catalog_product->getTotalProductsInBrand($filter_data);

        if (!empty($results_prods)) {
          if (isset($result['image']) && file_exists(DIR_IMAGE . $result['image'])) {
            $image = $this->model_tool_image->resize($result['image'], $width, $height);
          } else {
            $image = $this->model_tool_image->resize("no_image.png", $width, $height);
          }

          $data['manufacturers'][] = array(
            'name' => $result['name'],
            'image' => $image,
            'width' => $width,
            'height' => $height,
            'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $result['manufacturer_id'])
          );
        }
      }
    }

    $data['continue'] = $this->url->link('common/home');

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->response->setOutput($this->load->view('product/manufacturer_list', $data));
  }

  public function info()
  {
    $this->load->language('product/manufacturer');

    $this->load->model('catalog/manufacturer');
    $this->load->model('catalog/product');

    //режим каталогу
    if ($this->config->get('config_is_catalog')) {
      $data['config_is_catalog'] = $this->config->get('config_is_catalog');
    }

    if (isset($this->request->get['manufacturer_id'])) {
      $manufacturer_id = (int)$this->request->get['manufacturer_id'];
      $data['manufacturer_id'] = $manufacturer_id;
    } else {
      $manufacturer_id = 0;
      $data['manufacturer_id'] = 0;
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

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('product/manufacturer')
    );

    $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($manufacturer_id);

    if ($manufacturer_info) {

      $store_id = $this->config->get('config_store_id');
      if (isset($this->config->get('module_octhemeoption_custom_view')[$store_id])) {
        $data['use_custom_view'] = (int)$this->config->get('module_octhemeoption_custom_view')[$store_id];
      } else {
        $data['use_custom_view'] = 0;
      }

      if ($manufacturer_info['meta_title']) {
        $this->document->setTitle($manufacturer_info['meta_title']);
      } else {
        $this->document->setTitle($manufacturer_info['name']);
      }

      if ($manufacturer_info['noindex'] <= 0 && $this->config->get('config_noindex_status')) {
        $this->document->setRobots('noindex,follow');
      }

      $this->document->setKeywords($manufacturer_info['meta_keyword']);

      $this->load->model('setting/setting');
      $oct_replace = [
        '[name]' => strip_tags(html_entity_decode($manufacturer_info['name'], ENT_QUOTES, 'UTF-8')),
        '[address]' => $this->config->get('config_address'),
        '[phone]' => $this->config->get('config_telephone'),
        '[store]' => $this->config->get('config_name')
      ];

      if (isset($this->session->data['language'])) {
        $langId = $this->model_localisation_language->getLanguageByCode($this->session->data['language'])['language_id'];
      }

      //meta-h1
      if (!empty($manufacturer_info['meta_h1'])) {
        $data['heading_title'] = $manufacturer_info['meta_h1'];
      } else {
        $meta_h1 = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_gen_seo_brand_h1');
        if (!empty($meta_h1[$langId]['value'])) {
          $gen_seo_title = str_replace(array_keys($oct_replace), array_values($oct_replace), $meta_h1[$langId]['value']);
          $data['heading_title'] = $gen_seo_title;
        } else {
          $data['heading_title'] = $manufacturer_info['name'];
        }
      }

      //meta-title
      if (!empty($manufacturer_info['meta_title'])) {
        $this->document->setTitle($manufacturer_info['meta_title']);
      } else {
        $meta_title = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_gen_seo_brand_title');
        if (!empty($meta_title[$langId]['value'])) {
          $gen_seo_title = str_replace(array_keys($oct_replace), array_values($oct_replace), $meta_title[$langId]['value']);
          $this->document->setTitle($gen_seo_title);
        } else {
          $this->document->setTitle($manufacturer_info['name']);
        }
      }

      //meta-desc
      if (!empty($manufacturer_info['meta_description'])) {
        $this->document->setDescription($manufacturer_info['meta_description']);
      } else {
        $meta_desc = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_gen_seo_brand_meta_description');
        if (!empty($meta_desc[$langId]['value'])) {
          $gen_seo_title = str_replace(array_keys($oct_replace), array_values($oct_replace), $meta_desc[$langId]['value']);
          $this->document->setDescription($gen_seo_title);
        } else {
          $this->document->setDescription($manufacturer_info['name']);
        }
      }

      $data['description'] = html_entity_decode($manufacturer_info['description'], ENT_QUOTES, 'UTF-8');

      if ($manufacturer_info['image']) {
        $this->load->model('tool/image');
        $data['thumb'] = $this->model_tool_image->resize($manufacturer_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_manufacturer_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_manufacturer_height'));
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

      if (isset($this->request->get['limit'])) {
        $url .= '&limit=' . (int)$this->request->get['limit'];
      }

      $data['breadcrumbs'][] = array(
        'text' => $manufacturer_info['name'],
        'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . $url)
      );

      $data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

      $data['products'] = array();

      $filter_data = array(
        'filter_manufacturer_id' => $manufacturer_id,
        'filter' => isset($this->request->get['filter']) ? $this->request->get['filter'] : '',
        'sort' => $sort,
        'order' => $order,
        'start' => ($page - 1) * $limit,
        'limit' => $limit
      );

      if (isset($this->request->get['category_id'])) {
        $filter_data['filter_category_id'] = $this->request->get['category_id'];
      }

      $results = $this->model_catalog_product->getProducts($filter_data);
      $product_total = $results['total_products'];

      $mass_prod_id = array();

      if (!empty($results['products'])) {
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
            'quantity' => $options['quantity'],
            'rating' => (int)$result['rating'],
            'uniq_id' => $options['uniq_id'],
            'cart_id' => $options['cart_id'],
            'is_buy' => $options['is_buy'],
            'in_cart' => $options['in_cart'],
            'in_wishlist' => $options['in_wishlist'] ? 1 : 0,
            'date_end' => $options['date_end'],
            'on_stock' => $options['on_stock'],
            'statuses' => $options['statuses'],
            'href' => $this->url->link('product/product', 'manufacturer_id=' . $result['manufacturer_id'] . '&product_id=' . $result['product_id'] . $url)
          );
        }

        //получаем категории товаров
        if (!isset($this->request->get['ajax'])) {
          if (!empty($mass_prod_id)) {
            $data['category_tree'] = $this->load->controller('product/category_tree', $mass_prod_id);;
          }
        }
      }

      $url = '';

      if (isset($this->request->get['limit'])) {
        $url .= '&limit=' . (int)$this->request->get['limit'];
      }

      $data['sorts'] = array();

      $data['sorts'][] = array(
        'text' => $this->language->get('text_default'),
        'value' => 'p.sort_order-ASC',
        'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=p.sort_order&order=ASC' . $url)
      );

      $data['sorts'][] = array(
        'text' => $this->language->get('text_name_asc'),
        'value' => 'pd.name-ASC',
        'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=pd.name&order=ASC' . $url)
      );

      $data['sorts'][] = array(
        'text' => $this->language->get('text_name_desc'),
        'value' => 'pd.name-DESC',
        'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=pd.name&order=DESC' . $url)
      );

      $data['sorts'][] = array(
        'text' => $this->language->get('text_price_asc'),
        'value' => 'p.price-ASC',
        'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=p.price&order=ASC' . $url)
      );

      $data['sorts'][] = array(
        'text' => $this->language->get('text_price_desc'),
        'value' => 'p.price-DESC',
        'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&sort=p.price&order=DESC' . $url)
      );

      $pagination = new Pagination();
      $pagination->total = $product_total;
      $pagination->page = $page;
      $pagination->limit = $limit;
      $pagination->url = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . $url . '&page={page}');

      $data['pagination'] = $pagination->render();
      $data['page'] = $page;
      $data['total_pages'] = ceil($product_total / $limit);

      $data['manufacturer_id'] = $this->request->get['manufacturer_id'];

      $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

      if ($page == 2) {
        $this->document->addLink($this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id']), 'prev');
      } elseif ($page > 2) {
        $this->document->addLink($this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&page=' . ($page - 1)), 'prev');
      }
      if ($limit && ceil($product_total / $limit) > $page) {
        $this->document->addLink($this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . '&page=' . ($page + 1)), 'next');
      }

      $canonical_url = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id']);

      //seo попросили зробити canonical на категорію
      $keywords = explode('/', $canonical_url);
      $last_segment = end($keywords);
      $category_id = 0;
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($last_segment) . "'");

      if ($query->num_rows) {
        foreach ($query->rows as $row) {
          $url = explode('=', $row['query']);

          if ($url[0] == 'category_id') {
            $category_id = $url[1];
          }
        }
      }
      if ($category_id > 0){
        $canonical_url = $this->url->link('product/category', 'path=' . $category_id);
      }

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

      $data['schema_manufacturer'] = $this->load->controller('schema/manufacturer', $data);

      // Проверка, установлен ли модуль баннера
      $data['banner'] = $this->load->controller('extension/module/banner');

      if (isset($this->request->get['ajax'])) {
        return $this->load->view('product/category_ajax', $data);
      } else {
        $this->response->setOutput($this->load->view('product/manufacturer_info', $data));
      }
    } else {
      $url = '';

      if (isset($this->request->get['manufacturer_id'])) {
        $url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
      }

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
        'text' => $this->language->get('text_error'),
        'href' => $this->url->link('product/manufacturer/info', $url)
      );

      $this->document->setTitle($this->language->get('text_error'));

      $data['heading_title'] = $this->language->get('text_error');

      $data['text_error'] = $this->language->get('text_error');

      $data['continue'] = $this->url->link('common/home');

      $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

      $data['header'] = $this->load->controller('common/header');
      $data['footer'] = $this->load->controller('common/footer');
      $data['column_left'] = $this->load->controller('common/column_left');
      $data['column_right'] = $this->load->controller('common/column_right');
      $data['content_top'] = $this->load->controller('common/content_top');
      $data['content_bottom'] = $this->load->controller('common/content_bottom');

      $this->response->setOutput($this->load->view('error/not_found', $data));
    }
  }

  function buildCategoryTree($categories_info)
  {
    $tree = array();

    foreach ($categories_info as $category_id => $category) {
      $path = explode('_', $category['path']);
      $current_href = $this->url->link('product/category', 'path=' . $category['category_id']);
      // Починаємо з кореня дерева
      $current_level = &$tree;

      // Проходимо по всіх рівнях ієрархії
      foreach ($path as $parent_id) {
        if (!isset($current_level[$parent_id])) {
          $cat = $this->model_catalog_category->getCategory($parent_id);
          $href = $this->url->link('product/category', 'path=' . $cat['category_id']);
          $current_level[$parent_id] = array(
            'category_info' => $cat,
            'href' => $href,
            'children' => array()
          );
        }

        // Переходимо на наступний рівень
        $current_level = &$current_level[$parent_id]['children'];
      }

      // Додаємо поточну категорію
      $current_level[$category_id] = array(
        'category_info' => $category,
        'href' => $current_href,
        'children' => array()
      );
    }

    return $tree;
  }

}
