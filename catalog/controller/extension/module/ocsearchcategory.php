<?php

class ControllerExtensionModuleOcsearchcategory extends Controller
{
  public function index()
  {

    $this->load->language('extension/module/ocsearchcategory');

    $this->load->model('catalog/category');
    $this->load->model('tool/image');

    if (isset($this->request->get['q'])) {
      $data['q'] = $this->request->get['q'];
    } else {
      $data['q'] = '';
    }

    if (isset($this->request->get['category_id'])) {
      $category_id = $this->request->get['category_id'];
    } else {
      $category_id = 0;
    }

    $data['text_you_search'] = $this->language->get('text_you_search');
    $data['category_id'] = $category_id;
    $data['search_action'] = $this->url->link('product/search', '', true);
    $data['search_ajax_action'] = $this->url->link('extension/module/ocsearchcategory/ajaxSearch', '', true);
    $data['ocsearchcategory_ajax_enabled'] = $this->config->get('module_ocsearchcategory_ajax_enabled');
    $data['ocsearchcategory_loader_img'] = $this->config->get('config_url') . 'image/' . $this->config->get('module_ocsearchcategory_loader_img');

    return $this->load->view('extension/module/ocsearchcategory/ocsearchcategory', $data);
  }

  public function ajaxSearch()
  {

    $this->load->language('extension/module/ocsearchcategory');

    $json = array();

    if (isset($this->request->post['ajax'])) {
      if (($this->request->server['REQUEST_METHOD'] == 'POST' && $this->request->post['ajax'] == 'ajax_search')) {

        if (!$json) {
          $json['success'] = true;
        }

        $data['text_empty'] = $this->language->get('text_empty');

        $productCollection = $this->setAjaxSearchResult($this->request->post);
        if (!$productCollection || count($productCollection) == 0) {
          $data['products'] = array();
        } else {
          $data['products'] = $productCollection;
        }

        $data['product_img_enabled'] = (int)$this->config->get('module_ocsearchcategory_product_img');
        $data['product_price_enabled'] = (int)$this->config->get('module_ocsearchcategory_product_price');

        $json['result_html'] = $this->load->view('extension/module/ocsearchcategory/searchajaxresult', $data);

      } else {
        if (!$json) {
          $json['success'] = false;
        }
      }

      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }

  private function setAjaxSearchResult($data)
  {

    $text_search = $data['text_search'];

    $this->load->model('catalog/category');
    $this->load->model('catalog/product');
    $this->load->model('catalog/product_status');
    $this->load->model('tool/image');

    $data['is_mobile'] = $this->mobile_detect->isMobile();
    $width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width');
    $height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height');
    if ($data['is_mobile']) {
      $width = 150;
      $height = 150;
    }

    $url = '';

    if (isset($text_search)) {
      $search = $text_search;
      $url .= '&search=' . urlencode(html_entity_decode($text_search, ENT_QUOTES, 'UTF-8'));
    } else {
      $search = '';
    }

    $data['products'] = array();

    $filter_data = array(
      'filter_name'        => $search,
      'filter_search'      => $search,
      'filter_category_id' => 0,
      'limit'              => 10
    );

    $results = $this->model_catalog_product->getProducts($filter_data);
    if (!empty($results['products'])) {
      foreach ($results['products'] as $result) {

        //++Andrey
        $product_options = $this->model_catalog_product->getProductOptionPawPaw($result['product_id'], "ASC");

        $is_buy = false;
        $on_stock = false;
        $mass_options = array();
        $statuses = array();

        if (count($product_options) > 0) {
          foreach ($product_options as $option) {
            if ($option['product_option_id'] > 0) {
              if ($option['selected']) {
                if ($option['quantity'] > 0) {
                  $is_buy = true;
                  $on_stock = true;
                }
                $mass_options += [(int)$option['product_option_id'] => (int)$option['product_option_value_id']];

                if (!empty($option['image_opt']) && file_exists(DIR_IMAGE . $option['image_opt'])) {
                  $image = $option['image_opt'];
                } else {
                  $image = "no_image.png";
                }
              } else {
                if ($option['quantity'] > 0) {
                  $is_buy = true;
                  $on_stock = true;
                }
                if (!empty($result['image']) && file_exists(DIR_IMAGE . $result['image'])) {
                  $image = $result['image'];
                } else {
                  $image = "no_image.png";
                }
              }
            } else {
              if ($option['quantity'] > 0) {
                $is_buy = true;
                $on_stock = true;
              }
              if (!empty($result['image']) && file_exists(DIR_IMAGE . $result['image'])) {
                $image = $result['image'];
              } else {
                $image = "no_image.png";
              }
            }
          }
        } else {
          if ($result['quantity'] > 0) {
            $is_buy = true;
            $on_stock = true;
          }
          if (!empty($result['image']) && file_exists(DIR_IMAGE . $result['image'])) {
            $image = $result['image'];
          } else {
            $image = "no_image.png";
          }
        }

        $this->load->model('extension/module/discount');
        $prices_discount = $this->model_extension_module_discount->applyDisconts($result['product_id'], $mass_options);
        $price = $prices_discount['price'];
        $special = $prices_discount['special'] > 0 ? $prices_discount['special'] : false;
        $tax = $prices_discount['tax'] > 0 ? $prices_discount['tax'] : false;
        $percent = (int)$prices_discount['percent'] > 0 ? (int)$prices_discount['percent'] : false;

        if (!empty($mass_options)){
          $product_options = $this->model_catalog_product->getProductByOption($result['product_id'], $mass_options);
          $product_status_id = (int)$product_options['product_status_id'];
        }else{
          $product_status_id = (int)$result['product_status_id'];
        }
        if ($product_status_id > 0) {
          $statuses[$product_status_id] = $this->model_catalog_product_status->getProductStatus($product_status_id);
        }

        $data['products'][] = array(
          'product_id' => $result['product_id'],
          'thumb' => $this->model_tool_image->resize($image, $width, $height),
          'width' => $width,
          'height' => $height,
          'name' => $result['name'],
          'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
          'price' => $this->currency->format($price, $this->session->data['currency']),
          'special' => $special > 0 ? $this->currency->format($special, $this->session->data['currency']) : false,
          'tax' => $tax > 0 ? $this->currency->format($tax, $this->session->data['currency']) : false,
          'rate_special' => $percent,
          'is_buy' => $is_buy,
          'on_stock' => $on_stock,
          'statuses' => $statuses,
          'href' => $this->url->link('product/product', 'product_id=' . $result['product_id'] . $url)
        );
      }
    }
    return $data['products'];
  }

}
