<?php

class ControllerProductProduct extends Controller
{
  private $error = array();

  public function index()
  {

    $this->load->language('product/product');

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $this->load->model('setting/setting');
    $this->load->model('catalog/category');
    $this->load->model('catalog/product');
    $this->load->model('catalog/review');
    $this->load->model('catalog/manufacturer');

    $data['is_mobile'] = false;
    if ($this->mobile_detect->isMobile()) {
      $data['is_mobile'] = true;
    }

    //режим каталогу
    if ($this->config->get('config_is_catalog')) {
      $data['config_is_catalog'] = $this->config->get('config_is_catalog');
    }

    if (isset($this->request->get['path'])) {
      $path = '';

      $parts = explode('_', (string)$this->request->get['path']);

      $category_id = (int)array_pop($parts);

      foreach ($parts as $path_id) {
        if (!$path) {
          $path = $path_id;
        } else {
          $path .= '_' . $path_id;
        }

        $category_info = $this->model_catalog_category->getCategory($path_id);

        if ($category_info) {
          $data['breadcrumbs'][] = array(
            'text' => $category_info['name'],
            'href' => $this->url->link('product/category', 'path=' . $path)
          );
        }
      }

      // Set the last category breadcrumb
      $category_info = $this->model_catalog_category->getCategory($category_id);

      if ($category_info) {
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
          'text' => $category_info['name'],
          'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url)
        );
      }
    }

    if (isset($this->request->get['manufacturer_id'])) {
      $data['breadcrumbs'][] = array(
        'text' => $this->language->get('text_brand'),
        'href' => $this->url->link('product/manufacturer')
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

      $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($this->request->get['manufacturer_id']);

      if ($manufacturer_info) {
        $data['breadcrumbs'][] = array(
          'text' => $manufacturer_info['name'],
          'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . $url)
        );
      }
    }

    if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
      $url = '';

      if (isset($this->request->get['search'])) {
        $url .= '&search=' . $this->request->get['search'];
      }

      if (isset($this->request->get['tag'])) {
        $url .= '&tag=' . $this->request->get['tag'];
      }

      if (isset($this->request->get['description'])) {
        $url .= '&description=' . $this->request->get['description'];
      }

      if (isset($this->request->get['category_id'])) {
        $url .= '&category_id=' . $this->request->get['category_id'];
      }

      if (isset($this->request->get['sub_category'])) {
        $url .= '&sub_category=' . $this->request->get['sub_category'];
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
        'text' => $this->language->get('text_search'),
        'href' => $this->url->link('product/search', $url)
      );
    }

    if (isset($this->request->get['product_id'])) {
      $product_id = (int)$this->request->get['product_id'];
    } else {
      $product_id = 0;
    }

    $product_info = $this->model_catalog_product->getProduct($product_id);

    if ($product_info) {
      $url = '';

      if (isset($this->request->get['path'])) {
        $url .= '&path=' . $this->request->get['path'];
      }

      if (isset($this->request->get['filter'])) {
        $url .= '&filter=' . $this->request->get['filter'];
      }

      if (isset($this->request->get['manufacturer_id'])) {
        $url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
      }

      if (isset($this->request->get['search'])) {
        $url .= '&search=' . $this->request->get['search'];
      }

      if (isset($this->request->get['tag'])) {
        $url .= '&tag=' . $this->request->get['tag'];
      }

      if (isset($this->request->get['description'])) {
        $url .= '&description=' . $this->request->get['description'];
      }

      if (isset($this->request->get['category_id'])) {
        $url .= '&category_id=' . $this->request->get['category_id'];
      }

      if (isset($this->request->get['sub_category'])) {
        $url .= '&sub_category=' . $this->request->get['sub_category'];
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
        'text' => $product_info['name'],
        'href' => $this->url->link('product/product', $url . '&product_id=' . $this->request->get['product_id'])
      );

      $this->document->setKeywords($product_info['meta_keyword']);

      $oct_replace = [
        '[name]' => strip_tags(html_entity_decode($product_info['name'], ENT_QUOTES, 'UTF-8')),
        '[address]' => $this->config->get('config_address'),
        '[phone]' => $this->config->get('config_telephone'),
        '[store]' => $this->config->get('config_name')
      ];

      if (isset($this->session->data['language'])) {
        $langId = $this->model_localisation_language->getLanguageByCode($this->session->data['language'])['language_id'];
      }

      //meta-h1
      if (!empty($product_info['meta_h1'])) {
        $data['heading_title'] = $product_info['meta_h1'];
        $this->document->setMetaH1($data['heading_title']);
      }else {
        $meta_h1 = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_gen_seo_prod_h1');
        if (!empty($meta_h1[$langId]['value'])) {
          $gen_seo_h1 = str_replace(array_keys($oct_replace), array_values($oct_replace), $meta_h1[$langId]['value']);
          $data['heading_title'] = $gen_seo_h1;
          $this->document->setMetaH1($data['heading_title']);
        }else{
          $data['heading_title'] = $product_info['name'];
          $this->document->setMetaH1($data['heading_title']);
        }
      }

      //meta-title
      if (!empty($product_info['meta_title'])) {
        $this->document->setTitle($product_info['meta_title']);
      }else {
        $meta_title = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_gen_seo_prod_title');
        if (!empty($meta_title[$langId]['value'])) {
          $gen_seo_title = str_replace(array_keys($oct_replace), array_values($oct_replace), $meta_title[$langId]['value']);
          $this->document->setTitle($gen_seo_title);
        }else{
          $this->document->setTitle($product_info['name']);
        }
      }

      //meta-desc
      if (!empty($product_info['meta_description'])) {
        $this->document->setDescription($product_info['meta_description']);
      }else {
        $meta_desc = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_gen_seo_prod_meta_description');
        if (!empty($meta_desc[$langId]['value'])) {
          $gen_seo_meta = str_replace(array_keys($oct_replace), array_values($oct_replace), $meta_desc[$langId]['value']);
          $this->document->setDescription($gen_seo_meta);
        }else{
          $this->document->setDescription($product_info['name']);
        }
      }

      $this->document->addLink($this->url->link('product/product', 'product_id=' . $this->request->get['product_id']), 'canonical');

      $data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);

      $data['tab_review'] = sprintf($this->language->get('tab_review'), $product_info['reviews']);
      $data['text_description_calculation_result'] = $this->language->get('text_description_calculation_result');

      $data['product_id'] = (int)$this->request->get['product_id'];
      $data['manufacturer'] = $product_info['manufacturer'];
      $data['manufacturers'] = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $product_info['manufacturer_id']);

      $data['reward'] = $product_info['reward'];
      $data['points'] = $product_info['points'];
      $data['short_description'] = utf8_substr(trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..';

      $sort_order = "ASC";
      if (isset($this->request->get['order'])){
        $sort_order = $this->request->get['order'];
      }

      $product_data = array(
        'item' => $product_info,
        'sort_order' => $sort_order,
        'detal' => true
      );
      $options = $this->load->controller('product/options', $product_data);

      if (!empty($options['description'])){
        $data['description'] = html_entity_decode($options['description'], ENT_QUOTES, 'UTF-8');
      }else{
        $data['description'] = $this->language->get('text_empty_description');
      }

      if (!empty($options['composition'])){
        $data['composition'] = html_entity_decode($options['composition'], ENT_QUOTES, 'UTF-8');
      }else{
        $data['composition'] = html_entity_decode(Helper::cleanProductDescription($product_info['composition']), ENT_QUOTES, 'UTF-8');
      }

      $data['normi'] = '';
      if (!empty($product_info['normi'])){
        $data['normi'] = html_entity_decode(Helper::cleanProductDescription($product_info['normi']), ENT_QUOTES, 'UTF-8');
      }

      $data['calc'] = $product_info['calc'];
      $data['calc_template'] = $this->load->view('product/calculator', $data);

      $data['image_dop'] = $this->model_catalog_product->getProductImages($product_id);
      if (!empty($data['image_dop'])){
        $width = 600;
        $height = 600;
        $popup_width = 1000;
        $popup_height = 1000;
        foreach ($data['image_dop'] as &$img_dop){
          $img_dop['width'] = $width;
          $img_dop['height'] = $height;
          $img_dop['thumb'] = $this->model_tool_image->resize($img_dop['image'], $width, $height);
          $img_dop['popup'] = $this->model_tool_image->resize($img_dop['image'], $popup_width, $popup_height);
        }
      }

      $data['sku']          = $options["sku"];
      $data['model']        = $options["ean"];
      $data['images']       = $options["images"];
      $data['options']      = $options["prod_options"];
      $data['price']        = $this->currency->format($options['price'], $this->session->data['currency']);
      $data['special']      = $options['special'] > 0 ? $this->currency->format($options['special'], $this->session->data['currency']) : 0;
      $data['rate_special'] = $options['percent'];
      $data['quantity']     = $options['quantity'];
      $data["uniq_id"]      = $options['uniq_id'];
      $data['cart_id']      = $options['cart_id'];
      $data['in_cart']      = $options['in_cart'];
      $data['is_buy']       = $options['is_buy'];
      $data['on_stock']     = $options['on_stock'];
      $data['in_wishlist']  = $options['in_wishlist'] ? 1 : 0;
      $data['statuses']     = $options["statuses"];


      $data['review_status'] = $this->config->get('config_review_status');

      if ($this->config->get('config_review_guest') || $this->customer->isLogged()) {
        $data['review_guest'] = true;
      } else {
        $data['review_guest'] = false;
      }

      if ($this->customer->isLogged()) {
        $data['customer_name'] = $this->customer->getFirstName() . '&nbsp;' . $this->customer->getLastName();
      } else {
        $data['customer_name'] = '';
      }

      $data['count_reviews'] = (int)$product_info['reviews'];
      $data['reviews'] = sprintf($this->language->get('text_reviews'), $data['count_reviews']);
      $data['rating'] = (int)$product_info['rating'];

      // Captcha
      if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
        $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
      } else {
        $data['captcha'] = '';
      }

      //++Andrey
      $total_qty = 0;
      $stocks = $this->model_catalog_product->getProductOptStockWarehouses($product_id, $options['mass_options'], true);
      if (!empty($stocks)) {
        $this->load->language('extension/module/ocwarehouses');
        $data["title_stock_warehouses"] = $this->language->get('title_stock_warehouses');

        $this->load->model('extension/module/ocwarehouses');
        foreach ($stocks as $warehouse_id => $stock) {
          $warehouse = $this->model_extension_module_ocwarehouses->getWarehouseId($warehouse_id);
          $data["warehouses"][] = array(
            'name' => $warehouse['name'] ?? "",
            'quantity' => $stock
          );
          $total_qty += $stock;
        }
      }
      $data["total_qty"] = $total_qty;
      $data['stock'] = $this->load->view('extension/module/stockproduct', $data);

      $data['share'] = $this->url->link('product/product', 'product_id=' . (int)$this->request->get['product_id']);

      $data['attribute_groups'] = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);

      $data['module_custom_fastorder'] = (int)$this->config->get('module_custom_fastorder');

      //schema
      $data['schema_product'] = $this->load->controller('schema/product', $data);
      $data['schema_breadcrumbs'] = $this->load->view('schema/breadcrumbs', $data);

      //ecommerce
      $data['ecommerce_view_item'] = $this->load->controller('ecommerce/view_item', $data);

      //delivery payment
      $this->load->model('catalog/information');
      if ($this->customer->isLogged()) {
        if ((int)$this->customer->getGroupId() == 17){
          $information_info = $this->model_catalog_information->getInformation(13);
        } elseif ((int)$this->customer->getGroupId() != 17 && (int)$this->customer->getGroupId() != 21){
          $information_info = $this->model_catalog_information->getInformation(12);
        }
      }else{
        $information_info = $this->model_catalog_information->getInformation(6);
      }
      $data['text_more_delivery_payment'] = '<a class="policy bl-bold" href="' . $this->url->link('information/information', 'information_id=' . (int)$information_info['information_id']) . '">Детальніше</a>';









      // ---------- Рекомендовані товари ----------
      $data['products'] = array();

      $results = $this->model_catalog_product->getProductRelated($this->request->get['product_id']);

      foreach ($results as $result) {

        $sort_order = "ASC";
        if (isset($this->request->get['order'])){
          $sort_order = $this->request->get['order'];
        }

        $product_data = array(
          'item' => $result,
          'sort_order' => $sort_order
        );
        $options_related = $this->load->controller('product/options', $product_data);

        $data['products'][] = array(
          'product_id'    => $result['product_id'],
          'images'        => $options_related['images'],
          'options'       => $options_related['prod_options'],
          'name'          => (utf8_strlen($result['name']) > 50 ? utf8_substr($result['name'], 0, 50) . '..' : $result['name']),
          'price'         => $options_related['price'],
          'special'       => $options_related['special'],
          'rate_special'  => $options_related['percent'],
          'is_buy'        => $options_related['is_buy'],
          'uniq_id'       => $options_related['uniq_id'],
          'cart_id'       => $options_related['cart_id'],
          'in_cart'       => $options_related['in_cart'],
          'in_wishlist'   => $options_related['in_wishlist'] ? 1 : 0,
          'on_stock'      => $options_related['on_stock'],
          'statuses'      => $options_related['statuses'],
          'quantity'      => $options_related['quantity'],
          'rating'        => (int)$result['rating'],
          'href'          => $this->url->link('product/product', 'product_id=' . $result['product_id'])
        );
      }

      $data['tags'] = array();

      if ($product_info['tag']) {
        $tags = explode(',', $product_info['tag']);

        foreach ($tags as $tag) {
          $data['tags'][] = array(
            'tag' => trim($tag),
            'href' => $this->url->link('product/search', 'tag=' . trim($tag))
          );
        }
      }

      $data['recurrings'] = $this->model_catalog_product->getProfiles($this->request->get['product_id']);

      $this->model_catalog_product->updateViewed($this->request->get['product_id']);

      $data['column_left'] = $this->load->controller('common/column_left');
      $data['column_right'] = $this->load->controller('common/column_right');
      $data['content_top'] = $this->load->controller('common/content_top');
      $data['content_bottom'] = $this->load->controller('common/content_bottom');
      $data['footer'] = $this->load->controller('common/footer');
      $data['header'] = $this->load->controller('common/header');

      $this->response->setOutput($this->load->view('product/product', $data));
    } else {
      $url = '';

      if (isset($this->request->get['path'])) {
        $url .= '&path=' . $this->request->get['path'];
      }

      if (isset($this->request->get['filter'])) {
        $url .= '&filter=' . $this->request->get['filter'];
      }

      if (isset($this->request->get['manufacturer_id'])) {
        $url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
      }

      if (isset($this->request->get['search'])) {
        $url .= '&search=' . $this->request->get['search'];
      }

      if (isset($this->request->get['tag'])) {
        $url .= '&tag=' . $this->request->get['tag'];
      }

      if (isset($this->request->get['description'])) {
        $url .= '&description=' . $this->request->get['description'];
      }

      if (isset($this->request->get['category_id'])) {
        $url .= '&category_id=' . $this->request->get['category_id'];
      }

      if (isset($this->request->get['sub_category'])) {
        $url .= '&sub_category=' . $this->request->get['sub_category'];
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
        'href' => $this->url->link('product/product', $url . '&product_id=' . $product_id)
      );

      $this->document->setTitle($this->language->get('text_error'));

      $data['continue'] = $this->url->link('common/home');

      $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

      $data['column_left'] = $this->load->controller('common/column_left');
      $data['column_right'] = $this->load->controller('common/column_right');
      $data['content_top'] = $this->load->controller('common/content_top');
      $data['content_bottom'] = $this->load->controller('common/content_bottom');
      $data['footer'] = $this->load->controller('common/footer');
      $data['header'] = $this->load->controller('common/header');

      $this->response->setOutput($this->load->view('error/not_found', $data));
    }
  }

  public function review()
  {
    $this->load->language('product/product');

    $this->load->model('catalog/review');

    if (isset($this->request->get['page'])) {
      $page = (int)$this->request->get['page'];
    } else {
      $page = 1;
    }

    $data['reviews'] = array();

    $review_total = $this->model_catalog_review->getTotalReviewsByProductId($this->request->get['product_id']);

    $results = $this->model_catalog_review->getReviewsByProductId($this->request->get['product_id'], ($page - 1) * 5, 5);

    foreach ($results as $result) {
      $data['reviews'][] = array(
        'author' => $result['author'],
        'text' => nl2br($result['text']),
        'rating' => (int)$result['rating'],
        'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
      );
    }

    $pagination = new Pagination();
    $pagination->total = $review_total;
    $pagination->page = $page;
    $pagination->limit = 5;
    $pagination->url = $this->url->link('product/product/review', 'product_id=' . $this->request->get['product_id'] . '&page={page}');

    $data['pagination'] = $pagination->render();

    $data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * 5) + 1 : 0, ((($page - 1) * 5) > ($review_total - 5)) ? $review_total : ((($page - 1) * 5) + 5), $review_total, ceil($review_total / 5));

    $this->response->setOutput($this->load->view('product/review', $data));
  }

  public function add_review()
  {
    $this->load->language('product/product');

    if (isset($this->request->post['product_id'])) {
      $data['product_id'] = $this->request->post['product_id'];
    }

    // Captcha
    if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
      $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
    } else {
      $data['captcha'] = '';
    }

    $data['heading_title_add_review'] = $this->language->get('heading_title_add_review');

    $data['firstname'] = '';

    if ($this->customer->isLogged()) {
      $this->load->model('account/customer');
      $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
      if ($customer_info != null) {
        if (isset($this->request->post['firstname'])) {
          $data['firstname'] = $this->request->post['firstname'];
        } elseif (!empty($customer_info)) {
          $data['firstname'] = $customer_info['firstname'];
        }
      }
    }

    $json = $this->load->view('product/review_add', $data);

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));

  }

  public function write()
  {
    $this->load->language('product/product');

    $json = array();

    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 25)) {
        $this->error['name'] = $this->language->get('error_name');
      }

      if ((utf8_strlen($this->request->post['text']) < 25) || (utf8_strlen($this->request->post['text']) > 1000)) {
        $this->error['text'] = $this->language->get('error_text');
      }

      if (empty($this->request->post['rating']) || $this->request->post['rating'] < 0 || $this->request->post['rating'] > 5) {
        $this->error['rating'] = $this->language->get('error_rating');
      }

      // Captcha
      if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status')) {
        $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

        if ($captcha) {
          $this->error['captcha'] = $captcha;
        }
      }

      if (!$this->error) {
        $this->load->model('catalog/review');

        $this->model_catalog_review->addReview($this->request->post['product_id'], $this->request->post);

        $json['success'] = $this->language->get('text_success');
      }else{
        $json['error'] = $this->error;
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function getRecurringDescription()
  {
    $this->load->language('product/product');
    $this->load->model('catalog/product');

    if (isset($this->request->post['product_id'])) {
      $product_id = $this->request->post['product_id'];
    } else {
      $product_id = 0;
    }

    if (isset($this->request->post['recurring_id'])) {
      $recurring_id = $this->request->post['recurring_id'];
    } else {
      $recurring_id = 0;
    }

    if (isset($this->request->post['quantity'])) {
      $quantity = $this->request->post['quantity'];
    } else {
      $quantity = 1;
    }

    $product_info = $this->model_catalog_product->getProduct($product_id);

    $recurring_info = $this->model_catalog_product->getProfile($product_id, $recurring_id);

    $json = array();

    if ($product_info && $recurring_info) {
      if (!$json) {
        $frequencies = array(
          'day' => $this->language->get('text_day'),
          'week' => $this->language->get('text_week'),
          'semi_month' => $this->language->get('text_semi_month'),
          'month' => $this->language->get('text_month'),
          'year' => $this->language->get('text_year'),
        );

        if ($recurring_info['trial_status'] == 1) {
          $price = $this->currency->format($this->tax->calculate($recurring_info['trial_price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
          $trial_text = sprintf($this->language->get('text_trial_description'), $price, $recurring_info['trial_cycle'], $frequencies[$recurring_info['trial_frequency']], $recurring_info['trial_duration']) . ' ';
        } else {
          $trial_text = '';
        }

        $price = $this->currency->format($this->tax->calculate($recurring_info['price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

        if ($recurring_info['duration']) {
          $text = $trial_text . sprintf($this->language->get('text_payment_description'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
        } else {
          $text = $trial_text . sprintf($this->language->get('text_payment_cancel'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
        }

        $json['success'] = $text;
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

}
