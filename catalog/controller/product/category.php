<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerProductCategory extends Controller
{
  public function index()
  {
    $this->load->language('product/category');

    $this->load->model('catalog/category');
    $this->load->model('catalog/product_status');
    $this->load->model('catalog/product');

    $this->load->model('tool/image');

    $data['text_empty'] = $this->language->get('text_empty');
    $data['img_empty'] = $this->model_tool_image->resize("no_image.png", 400, 400);
    $data['continue'] = $this->url->link('common/home');

    $data['text_buy'] = $this->language->get('text_buy');

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

    if (isset($this->request->get['filter'])) {
      $filter = $this->request->get['filter'];
      if (!in_array('filter', $disallow_params, true) && $this->config->get('config_noindex_status')) {
        $this->document->setRobots('noindex,follow');
      }
    } else {
      $filter = '';
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

    if (isset($this->request->get['path'])) {
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

      $path = '';

      $parts = explode('_', (string)$this->request->get['path']);

      $category_id = (int)array_pop($parts);

      foreach ($parts as $path_id) {
        if (!$path) {
          $path = (int)$path_id;
        } else {
          $path .= '_' . (int)$path_id;
        }

        $category_info = $this->model_catalog_category->getCategory($path_id);

        if ($category_info) {
          $data['breadcrumbs'][] = array(
            'text' => $category_info['name'],
            'href' => $this->url->link('product/category', 'path=' . $path . $url)
          );
        }
      }

      $data['path'] = $this->request->get['path'];

    } else {
      $category_id = 0;
    }

    $category_info = $this->model_catalog_category->getCategory($category_id);

    if ($category_info) {

      $data['category_id'] = $category_id;

      if (!empty($category_info['description'])) {
        // Використовуємо strip_tags з дозволеними тегами для збереження структури
        $description = stripslashes($category_info['description']);
        $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
        $description = trim(strip_tags($description, '<br><p><span>')); // дозволяємо деякі теги

        $description = str_replace(["rn"], " ", $description); // або "<br>" замість пробілу

        // Якщо потрібно конвертувати переноси в <br>
        $description = nl2br($description);

        $category_info['description'] = $description;
      }

      if ($category_info['meta_title']) {
        $this->document->setTitle($category_info['meta_title']);
      } else {
        $this->document->setTitle($category_info['name']);
      }

      if ($category_info['noindex'] <= 0 && $this->config->get('config_noindex_status')) {
        $this->document->setRobots('noindex,follow');
      }

      $this->document->setKeywords($category_info['meta_keyword']);

      $this->load->model('setting/setting');
      $oct_replace = [
        '[name]' => strip_tags(html_entity_decode($category_info['name'], ENT_QUOTES, 'UTF-8')),
        '[address]' => $this->config->get('config_address'),
        '[phone]' => $this->config->get('config_telephone'),
        '[store]' => $this->config->get('config_name')
      ];

      if (isset($this->session->data['language'])) {
        $langId = $this->model_localisation_language->getLanguageByCode($this->session->data['language'])['language_id'];
      }

      //meta-h1
      if (!empty($category_info['meta_h1'])) {
        $data['heading_title'] = $category_info['meta_h1'];
      } else {
        $meta_h1 = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_gen_seo_cat_h1');
        if (!empty($meta_h1[$langId]['value'])) {
          $gen_seo_title = str_replace(array_keys($oct_replace), array_values($oct_replace), $meta_h1[$langId]['value']);
          $data['heading_title'] = $gen_seo_title;
        } else {
          $data['heading_title'] = $category_info['name'];
        }
      }

      //meta-title
      if (!empty($category_info['meta_title'])) {
        $this->document->setTitle($category_info['meta_title']);
      } else {
        $meta_title = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_gen_seo_cat_title');
        if (!empty($meta_title[$langId]['value'])) {
          $gen_seo_title = str_replace(array_keys($oct_replace), array_values($oct_replace), $meta_title[$langId]['value']);
          $this->document->setTitle($gen_seo_title);
        } else {
          $this->document->setTitle($category_info['name']);
        }
      }

      //meta-desc
      if (!empty($category_info['meta_description'])) {
        $this->document->setDescription($category_info['meta_description']);
      } else {
        $meta_desc = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_gen_seo_cat_meta_description');
        if (!empty($meta_desc[$langId]['value'])) {
          $gen_seo_title = str_replace(array_keys($oct_replace), array_values($oct_replace), $meta_desc[$langId]['value']);
          $this->document->setDescription($gen_seo_title);
        } else {
          $this->document->setDescription($category_info['name']);
        }
      }

      // Set the last category breadcrumb
      $data['breadcrumbs'][] = array(
        'text' => $category_info['name'],
        'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'])
      );

      if (!empty($category_info['image'])) {
        $data['thumb'] = $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
      } else {
        $data['thumb'] = $this->model_tool_image->resize("no_image.png", $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
      }

      $data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');

      $url = '';

      // Отримуємо параметри фільтра з URL (наприклад: "brand-lucky-pet/color-grey")
      if (isset($this->request->get['filter'])) {
        $url .= '&filter=' . urlencode($this->request->get['filter']);
      }

      if (isset($this->request->get['limit'])) {
        $url .= '&limit=' . (int)$this->request->get['limit'];
      }

      $url_no_sort = $url;

      if (isset($this->request->get['sort'])) {
        $url .= '&sort=' . $this->request->get['sort'];
      }

      if (isset($this->request->get['order'])) {
        $url .= '&order=' . $this->request->get['order'];
      }

      $data['categories'] = array();

      $category_type = 0;
      if (isset($this->request->post['switch_menu'])) {
        $category_type = (int)$this->request->post['switch_menu'];
      } elseif (isset($this->request->cookie['catalog_type'])) {
        $category_type = (int)$this->request->cookie['catalog_type'];
      }

      $results = $this->model_catalog_category->getCategoriesCatalog($category_id, $category_type);

      if (!empty($results)) {
        foreach ($results as $result) {
          if (!empty($result['image'])) {
            $thumb = $result['image'];
          } else {
            $thumb = 'no_image.png';
          }

          $data['categories'][] = array(
            'thumb' => $this->model_tool_image->resize($thumb, 250, 250),
            'category_id' => $result['category_id'],
            'name' => $result['name'],
            'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '_' . $result['category_id'] . $url)
          );
        }
      }

      $data['img_category_pdf_width'] = 450;
      $data['img_category_pdf_height'] = 450;
      if (file_exists(DIR_IMAGE . "catalog-pdf.png")) {
        $img_category_pdf = $this->model_tool_image->resize("catalog-pdf.png", $data['img_category_pdf_width'], $data['img_category_pdf_height']);
      } else {
        $img_category_pdf = $this->model_tool_image->resize("no_image.png", $data['img_category_pdf_width'], $data['img_category_pdf_height']);
      }
      $data['img_category_pdf'] = $img_category_pdf;

      //++Andrey

      $data['products'] = array();

      $filter_data = array(
        'filter_category_id' => $category_id,
        'filter' => $filter,
        'sort' => $sort,
        'order' => $order,
        'start' => ($page - 1) * $limit,
        'limit' => $limit
      );

      $results = $this->model_catalog_product->getProducts($filter_data);
      $product_total = $results['total_products'];
      if (isset($results['products'])) {

        $sort_order = "ASC";
        if (isset($this->request->get['order'])) {
          $sort_order = $this->request->get['order'];
        }

        foreach ($results['products'] as $result) {

          $product_data = array(
            'item' => $result,
            'sort_order' => $sort_order
          );
          $options = $this->load->controller('product/options', $product_data);

          $data['products'][] = array(
            'product_id' => $result['product_id'],
            'name' => (utf8_strlen($result['name']) > 60 ? utf8_substr($result['name'], 0, 60) . '..' : $result['name']),
            'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
            'price' => $this->currency->format($options['price'], $this->session->data['currency']),
            'rating' => (int)$result['rating'],
            'special' => $options['special'] > 0 ? $this->currency->format($options['special'], $this->session->data['currency']) : false,
            'rate_special' => $options['percent'],
            'images' => $options['images'],
            'options' => $options['prod_options'],
            'is_buy' => $options['is_buy'],
            'quantity' => $options['quantity'],
            'uniq_id' => $options['uniq_id'],
            'cart_id' => $options['cart_id'],
            'in_cart' => $options['in_cart'],
            'in_wishlist' => $options['in_wishlist'] ? 1 : 0,
            'statuses' => $options['statuses'],
            'on_stock' => $options['on_stock'],
            'date_end' => $options['date_end'],
            'href' => $this->url->link('product/product', 'path=' . $this->request->get['path'] . '&product_id=' . $result['product_id'] . $url)
          );
        }
      }

      $data['sorts'] = array();

      $data['sorts'][] = array(
        'text' => $this->language->get('text_default'),
        'value' => 'p.sort_order-ASC',
        'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.sort_order&order=ASC' . $url_no_sort)
      );

      $data['sorts'][] = array(
        'text' => $this->language->get('text_name_asc'),
        'value' => 'pd.name-ASC',
        'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=ASC' . $url_no_sort)
      );

      $data['sorts'][] = array(
        'text' => $this->language->get('text_name_desc'),
        'value' => 'pd.name-DESC',
        'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=DESC' . $url_no_sort)
      );

      $data['sorts'][] = array(
        'text' => $this->language->get('text_price_asc'),
        'value' => 'price-ASC',
        'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=price&order=ASC' . $url_no_sort)
      );

      $data['sorts'][] = array(
        'text' => $this->language->get('text_price_desc'),
        'value' => 'price-DESC',
        'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=price&order=DESC' . $url_no_sort)
      );

      // Проверка, установлен ли модуль баннера
      $data['banner'] = $this->load->controller('extension/module/banner');

      $pagination = new Pagination();
      $pagination->total = $product_total;
      $pagination->page = $page;
      $pagination->limit = $limit;

      // Формуємо URL з урахуванням фільтрів
      $url_params = 'path=' . $this->request->get['path'];

      // Додаємо параметр filter, якщо він є
      if (isset($this->request->get['filter'])) {
        $url_params .= '&filter=' . urlencode($this->request->get['filter']);
      }

      // Додаємо &page={page} в кінець
      $pagination->url = $this->url->link('product/category', $url_params . '&page={page}');

      $data['pagination'] = $pagination->render();
      $data['page'] = $page;
      $data['total_pages'] = ceil($product_total / $limit);

      $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

      if ($page == 2) {
        $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id']), 'prev');
      } elseif ($page > 2) {
        $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page=' . ($page - 1)), 'prev');
      }
      if ($limit && ceil($product_total / $limit) > $page) {
        $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page=' . ($page + 1)), 'next');
      }

      $canonical_url = $this->url->link('product/category', 'path=' . $category_info['category_id']);
      $this->document->addLink($canonical_url, 'canonical');

      if ($page > 1){
        $data['heading_title'] = $data['heading_title'] . " | сторінка " . $page;
        $this->document->setTitle($this->document->getTitle() . " | сторінка " . $page);
      }

      $data['category_name'] = $category_info['name'];
      $data['catalog_pdf'] = $category_info['catalog'];

      $data['sort'] = $sort;
      $data['order'] = $order;
      $data['limit'] = $limit;


      $data['debug_ag'] = DEBUG_AG;



      $data['schema_category'] = $this->load->controller('schema/category', $data);

      if (isset($this->request->get['ajax'])) {
        return $this->load->view('product/category_ajax', $data);
      }

      $data['column_left'] = $this->load->controller('common/column_left');
      $data['column_right'] = $this->load->controller('common/column_right');
      $data['content_top'] = $this->load->controller('common/content_top');
      $data['content_bottom'] = $this->load->controller('common/content_bottom');
      $data['footer'] = $this->load->controller('common/footer');
      $data['header'] = $this->load->controller('common/header');

      $this->response->setOutput($this->load->view('product/category', $data));
    } else {
      $url = '';

      if (isset($this->request->get['path'])) {
        $url .= '&path=' . $this->request->get['path'];
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
        'href' => $this->url->link('product/category', $url)
      );

      $this->document->setTitle($this->language->get('text_error'));

      $data['continue'] = $this->url->link('common/home');

      $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

      if (isset($this->request->get['ajax'])) {
        $this->response->setOutput($this->load->view('error/not_found', $data));
        return;
      }

      $data['column_left'] = $this->load->controller('common/column_left');
      $data['column_right'] = $this->load->controller('common/column_right');
      $data['content_top'] = $this->load->controller('common/content_top');
      $data['content_bottom'] = $this->load->controller('common/content_bottom');
      $data['footer'] = $this->load->controller('common/footer');
      $data['header'] = $this->load->controller('common/header');

      $this->response->setOutput($this->load->view('error/not_found', $data));
    }
  }
}
