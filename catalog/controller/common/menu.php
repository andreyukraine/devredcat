<?php

class ControllerCommonMenu extends Controller
{
  public function index()
  {
    $this->load->language('common/menu');
    $this->load->language('common/header');

    // Menu
    $this->load->model('catalog/category');
    $this->load->model('catalog/product');

    $this->load->model('catalog/manufacturer');

    if ($this->request->server['HTTPS']) {
      $server = $this->config->get('config_ssl');
    } else {
      $server = $this->config->get('config_url');
    }

    $data['home'] = $this->url->link('common/home');
    $data['comment'] = $this->config->get('config_comment');

    if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
      $data['logo'] = $server . 'image/' . $this->config->get('config_logo');
    } else {
      $data['logo'] = '';
    }

    $data['language'] = $this->load->controller('common/language');
    $data['currency'] = $this->load->controller('common/currency');

    $data['logged'] = $this->customer->isLogged();
    $data['account'] = $this->url->link('account/account', '', true);
    $data['register'] = $this->url->link('account/register', '', true);
    $data['login'] = $this->url->link('account/login', '', true);
    $data['order'] = $this->url->link('account/order', '', true);
    $data['transaction'] = $this->url->link('account/transaction', '', true);
    $data['download'] = $this->url->link('account/download', '', true);
    $data['logout'] = $this->url->link('account/logout', '', true);
    $data['shopping_cart'] = $this->url->link('checkout/cart');
    $data['checkout'] = $this->url->link('checkout/checkout', '', true);
    $data['contact'] = $this->url->link('information/contact');
    $data['telephone'] = $this->config->get('config_telephone');


    $data['categories'] = array();
    $promo = array();

    $data['category_type'] = 0;

    $config_menu_product_count = $this->config->get('config_menu_product_count');


    if (isset($this->request->post['switch_menu'])) {
      $data['category_type'] = (int)$this->request->post['switch_menu'];
    } elseif (isset($this->request->cookie['catalog_type'])) {
      $data['category_type'] = (int)$this->request->cookie['catalog_type'];
    }

    if ((int)$data['category_type'] > 0){
      $data['type_text'] = $this->language->get('type_text_type');
      $data['type_check'] = $this->language->get('type_check_brand');
      $data['type_class'] = "chek-right";
    }else{
      $data['type_text'] = $this->language->get('type_text_brand');
      $data['type_check'] = $this->language->get('type_check_type');
      $data['type_class'] = "chek-left";
    }

    if ($this->customer->isLogged()) {
      $customer_group_id = $this->customer->getGroupId();
    } else {
      $customer_group_id = $this->config->get('config_customer_group_id');
    }

    $cache_key = 'menu.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . (int)$data['category_type'];
    $cached_data = $this->cache->get($cache_key);

    if ($cached_data) {
      $data['categories'] = $cached_data['categories'];
      $data['discont_total'] = $cached_data['discont_total'];
      $data['manufacturer_total'] = $cached_data['manufacturer_total'];
      $data['config_soc_telegram'] = $cached_data['config_soc_telegram'];
      $data['config_soc_facebook'] = $cached_data['config_soc_facebook'];
      $data['config_soc_instagram'] = $cached_data['config_soc_instagram'];
      $data['config_soc_youtube'] = $cached_data['config_soc_youtube'];
    } else {
      $categories = $this->model_catalog_category->getCategories(0, $data['category_type']);

      $data['config_soc_telegram'] = $this->config->get('config_soc_telegram');
      $data['config_soc_facebook'] = $this->config->get('config_soc_facebook');
      $data['config_soc_instagram'] = $this->config->get('config_soc_instagram');
      $data['config_soc_youtube'] = $this->config->get('config_soc_youtube');

      $filter_data = array(
        'customer_group_id' => $customer_group_id
      );
      $discont_total = $this->model_catalog_product->getProductsDiscont($filter_data);
      $data['discont_total'] = $discont_total['total_products'];

      $data['manufacturer_total'] = $this->model_catalog_manufacturer->getTotalManufacturers();

      foreach ($categories as $category) {
        // Level 2
        $children_data = array();

        $children = $this->model_catalog_category->getCategories($category['category_id'], $data['category_type']);

        foreach ($children as $child) {

          // Level 3
          $children_data3 = array();
          $children3 = $this->model_catalog_category->getCategories($child['category_id'], $data['category_type']);

          foreach ($children3 as $child3) {

            $child3_color = "#f6f7f7";
            $filter_data3 = array(
              'filter_category_id' => $child3['category_id']
            );
            $total_3 = $this->model_catalog_product->getTotalProductsInCategory($filter_data3);
            if (!$config_menu_product_count) {
              if ($total_3 > 0) {
                if ($child3['featured_color'] != null) {$child3_color = $child3['featured_color'];}
                $children_data3[] = array(
                  'id' => $child3['category_id'],
                  'name' => $child3['name'],
                  'color' => $child3_color,
                  'count' => $total_3,
                  'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child3['category_id'])
                );
              }
            }else{
              if ($child3['featured_color'] != null) {$child3_color = $child3['featured_color'];}
              $children_data3[] = array(
                'id' => $child3['category_id'],
                'name' => $child3['name'],
                'color' => $child3_color,
                'count' => $total_3,
                'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child3['category_id'])
              );
            }
          }

          $child_color = "#f6f7f7";
          $filter_data2 = array(
            'filter_category_id' => $child['category_id']
          );
          $total_2 = $this->model_catalog_product->getTotalProductsInCategory($filter_data2);
          if (!$config_menu_product_count) {
            if ($total_2 > 0) {
              if ($child['featured_color'] != null) {$child_color = $child['featured_color'];}
              $children_data[] = array(
                'id' => $child['category_id'],
                'children' => $children_data3,
                'count' => $total_2,
                'color' => $child_color,
                'name' => $child['name'],
                'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
              );
            }
          }else{
            if ($child['featured_color'] != null) {$child_color = $child['featured_color'];}
            $children_data[] = array(
              'id' => $child['category_id'],
              'children' => $children_data3,
              'count' => $total_2,
              'color' => $child_color,
              'name' => $child['name'],
              'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
            );
          }
        }

        // Level 1

        $category_color = "#f6f7f7";
        $filter_data1 = array(
          'filter_category_id' => $category['category_id']
        );
        $total_1 = $this->model_catalog_product->getTotalProductsInCategory($filter_data1);
        if (!$config_menu_product_count) {
          if ($total_1 > 0) {
            if ($category['featured_color'] != null) {$category_color = $category['featured_color'];}

            $data['categories'][] = array(
              'id' => $category['category_id'],
              'name' => $category['name'],
              'color' => $category_color,
              'count' => $total_1,
              'children' => $children_data,
              'column' => $category['column'] ? $category['column'] : 1,
              'href' => $this->url->link('product/category', 'path=' . $category['category_id'])
            );
          }
        }else{
          if ($category['featured_color'] != null) {$category_color = $category['featured_color'];}

          $data['categories'][] = array(
            'id' => $category['category_id'],
            'name' => $category['name'],
            'color' => $category_color,
            'count' => $total_1,
            'children' => $children_data,
            'column' => $category['column'] ? $category['column'] : 1,
            'href' => $this->url->link('product/category', 'path=' . $category['category_id'])
          );
        }
      }

      $this->cache->set($cache_key, [
        'categories' => $data['categories'],
        'discont_total' => $data['discont_total'],
        'manufacturer_total' => $data['manufacturer_total'],
        'config_soc_telegram' => $data['config_soc_telegram'],
        'config_soc_facebook' => $data['config_soc_facebook'],
        'config_soc_instagram' => $data['config_soc_instagram'],
        'config_soc_youtube' => $data['config_soc_youtube']
      ]);
    }

    if (isset($this->request->get['ajax'])){
      return $this->load->view('common/menu_fixed_type', $data);
    }else {
      return $this->load->view('common/menu_fixed', $data);
    }
  }

  public function change_menu(){

    $this->load->language('common/menu');

    $json = array();
    $json['status'] = false;
    if (isset($this->request->post['switch_menu'])) {
      $json['status'] = true;
      $this->request->get['ajax'] = 1;
      if ((int)$this->request->post['switch_menu'] > 0){
        $json['text'] = $this->language->get('type_text_type');
        $json['type_check'] = $this->language->get('type_check_brand');
        $json['type_class'] = "chek-right";
      }else{
        $json['text'] = $this->language->get('type_text_brand');
        $json['type_check'] = $this->language->get('type_check_type');
        $json['type_class'] = "chek-left";
      }
      $this->session->data['catalog_type'] = (int)$this->request->post['switch_menu'];
      setcookie('catalog_type', (int)$this->request->post['switch_menu'], time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);

      $json['html'] = $this->index();
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function menuTopMain()
  {
    $this->load->language('information/contact');
    $this->load->language('information/map');
    $this->load->language('extension/module/ajaxlogin');

    $this->load->model('tool/image');

    //++Andrey
    $data['map'] = $this->load->controller('information/map');

    $data['is_mobile'] = $this->mobile_detect->isMobile();

    $data['user_logo_width'] = 80;
    $data['user_logo_height'] = 80;
    if ($data['is_mobile']){
      $data['user_logo_width'] = 35;
      $data['user_logo_height'] = 35;
    }

    $data['locations'] = array();

    $this->load->model('localisation/location');

    foreach ((array)$this->config->get('config_location') as $location_id) {
      $location_info = $this->model_localisation_location->getLocation($location_id);

      if ($location_info) {
        if ($location_info['image']) {
          $image = $this->model_tool_image->resize($location_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_height'));
        } else {
          $image = false;
        }

        $data['locations'][] = array(
          'location_id' => $location_info['location_id'],
          'name' => $location_info['name'],
          'address' => nl2br($location_info['address']),
          'geocode' => $location_info['geocode'],
          'telephone' => $location_info['telephone'],
          'fax' => $location_info['fax'],
          'image' => $image,
          'open' => html_entity_decode($location_info['open']),
          'comment' => $location_info['comment']
        );
      }
    }

    $data['menu_top'] = $this->load->controller('common/menu_top');

    $data['language'] = $this->load->controller('common/language');

    $data['text_social'] = $this->language->get('text_social');

    $data['config_address'] = $this->config->get('config_address');

    $data['config_soc_telegram'] = $this->config->get('config_soc_telegram');
    $data['config_soc_facebook'] = $this->config->get('config_soc_facebook');
    $data['config_soc_instagram'] = $this->config->get('config_soc_instagram');
    $data['config_soc_youtube'] = $this->config->get('config_soc_youtube');

    $data['telephone'] = $this->config->get('config_telephone');
    $data['telephone_1'] = $this->config->get('config_telephone_1');
    $data['telephone_2'] = $this->config->get('config_telephone_2');
    $data['telephone_3'] = $this->config->get('config_telephone_3');
    $data['open'] = nl2br(html_entity_decode($this->config->get('config_open')));

    $data['text_account'] = $this->language->get('text_account');
    $data['text_logout'] = $this->language->get('text_logout');
    $data['is_login'] = $this->customer->isLogged();

    $data['account'] = $this->url->link('account/account', '', true);
    $data['logout'] = $this->url->link('account/logout', '', true);

    $data['text_working_hours'] = $this->language->get("text_working_hours");
    $data['text_view_map'] = $this->language->get("text_view_map");

    $this->response->setOutput($this->load->view('common/menu_top_main', $data));
  }
}
