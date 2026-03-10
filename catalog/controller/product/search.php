<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerProductSearch extends Controller
{
  public function index()
  {
    $this->load->language('product/search');

    $this->load->model('catalog/category');
    $this->load->model('catalog/product_status');
    $this->load->model('catalog/product');
    $this->load->model('tool/image');

    $width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width');
    $height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height');
    if ($this->mobile_detect->isMobile()) {
      $width = 150;
      $height = 150;
    }

    $data['text_empty'] = $this->language->get('text_empty');
    $data['img_empty'] = $this->model_tool_image->resize("no_image.png", 400, 400);
    $data['continue'] = $this->url->link('common/home');

    //режим каталогу
    if ($this->config->get('config_is_catalog')) {
      $data['config_is_catalog'] = $this->config->get('config_is_catalog');
    }

    if (isset($this->request->get['q'])) {
      $search = $this->request->get['q'];
    } else {
      $search = '';
    }

    if (isset($this->request->get['sort'])) {
      $sort = $this->request->get['sort'];
    } else {
      $sort = 'p.sort_order';
    }

    if (isset($this->request->get['order'])) {
      $order = $this->request->get['order'];
    } else {
      $order = 'ASC';
    }

    if (isset($this->request->get['page'])) {
      $page = (int)$this->request->get['page'];
    } else {
      $page = 1;
    }

    if (isset($this->request->get['limit'])) {
      $limit = (int)$this->request->get['limit'];
    } else {
      $limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
    }

    if (isset($this->request->get['q'])) {
      $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->request->get['q']);
    } elseif (isset($this->request->get['tag'])) {
      $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('heading_tag') . $this->request->get['tag']);
    } else {
      $this->document->setTitle($this->language->get('heading_title'));
    }

    $this->document->setRobots('noindex,follow');

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $url = '';

    if (isset($this->request->get['q'])) {
      $url .= '&q=' . urlencode(html_entity_decode($this->request->get['q'], ENT_QUOTES, 'UTF-8'));
    }

    if (isset($this->request->get['tag'])) {
      $url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
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
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('product/search', $url)
    );

    if (isset($this->request->get['q'])) {
      $data['heading_title'] = $this->language->get('heading_title') . ' - ' . $this->request->get['q'];
    } else {
      $data['heading_title'] = $this->language->get('heading_title');
    }

    $this->document->setRobots('noindex,follow');

    $data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

    // 3 Level Category Search
    $data['categories'] = array();

    $categories_1 = $this->model_catalog_category->getCategories(0);

    foreach ($categories_1 as $category_1) {
      $level_2_data = array();

      $categories_2 = $this->model_catalog_category->getCategories($category_1['category_id']);

      foreach ($categories_2 as $category_2) {
        $level_3_data = array();

        $categories_3 = $this->model_catalog_category->getCategories($category_2['category_id']);

        foreach ($categories_3 as $category_3) {
          $level_3_data[] = array(
            'category_id' => $category_3['category_id'],
            'name' => $category_3['name'],
          );
        }

        $level_2_data[] = array(
          'category_id' => $category_2['category_id'],
          'name' => $category_2['name'],
          'children' => $level_3_data
        );
      }

      $data['categories'][] = array(
        'category_id' => $category_1['category_id'],
        'name' => $category_1['name'],
        'children' => $level_2_data
      );
    }

    $data['products'] = array();

    $data['sorts'] = array();

    $data['sorts'][] = array(
      'text' => $this->language->get('text_default'),
      'value' => 'p.sort_order-ASC',
      'href' => $this->url->link('product/search', 'sort=p.sort_order&order=ASC' . $url)
    );

    $data['sorts'][] = array(
      'text' => $this->language->get('text_name_asc'),
      'value' => 'pd.name-ASC',
      'href' => $this->url->link('product/search', 'sort=pd.name&order=ASC' . $url)
    );

    $data['sorts'][] = array(
      'text' => $this->language->get('text_name_desc'),
      'value' => 'pd.name-DESC',
      'href' => $this->url->link('product/search', 'sort=pd.name&order=DESC' . $url)
    );

    $data['sorts'][] = array(
      'text' => $this->language->get('text_price_asc'),
      'value' => 'p.price-ASC',
      'href' => $this->url->link('product/search', 'sort=p.price&order=ASC' . $url)
    );

    $data['sorts'][] = array(
      'text' => $this->language->get('text_price_desc'),
      'value' => 'p.price-DESC',
      'href' => $this->url->link('product/search', 'sort=p.price&order=DESC' . $url)
    );

    $store_id = $this->config->get('config_store_id');
    $data['use_quickview'] = (int)$this->config->get('module_octhemeoption_quickview')[$store_id];

    if (isset($this->request->get['q'])) {
      $filter_data = array(
        'filter_search' => $search,
        'filter_name' => $search,
        'filter' => isset($this->request->get['filter']) ? $this->request->get['filter'] : '',
        'filter_description' => isset($this->request->get['description']) ? $this->request->get['description'] : '',
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
            'product_id'    => $result['product_id'],
            'images'        => $options['images'],
            'options'       => $options['prod_options'],
            'name'          => (utf8_strlen($result['name']) > 60 ? utf8_substr($result['name'], 0, 60) . '..' : $result['name']),
            'price'         => $this->currency->format($options['price'], $this->session->data['currency']),
            'special'       => $options['special'] > 0 ? $this->currency->format($options['special'], $this->session->data['currency']) : false,
            'rate_special'  => $options['percent'],
            'is_buy'        => $options['is_buy'],
            'quantity'      => $options['quantity'],
            'rating'        => (int)$result['rating'],
            'uniq_id'       => $options['uniq_id'],
            'cart_id'       => $options['cart_id'],
            'in_cart'       => $options['in_cart'],
            'in_wishlist'   => $options['in_wishlist'] ? 1 : 0,
            'date_end'      => $options['date_end'],
            'on_stock'      => $options['on_stock'],
            'statuses'      => $options['statuses'],
            'href'          => $this->url->link('product/product', 'product_id=' . $result['product_id'] . $url)
          );
        }

        //получаем категории товаров
        if (!isset($this->request->get['ajax'])){
          if (!empty($mass_prod_id)) {
            $data['category_tree'] = $this->load->controller('product/category_tree', $mass_prod_id);;
          }
        }
      }

      $url = '';

      if (isset($this->request->get['q'])) {
        $url .= '&q=' . urlencode(html_entity_decode($this->request->get['q'], ENT_QUOTES, 'UTF-8'));
      }

      if (isset($this->request->get['limit'])) {
        $url .= '&limit=' . $this->request->get['limit'];
      }

      $url = '';

      if (isset($this->request->get['q'])) {
        $url .= '&q=' . urlencode(html_entity_decode($this->request->get['q'], ENT_QUOTES, 'UTF-8'));
      }

      if (isset($this->request->get['sort'])) {
        $url .= '&sort=' . $this->request->get['sort'];
      }

      if (isset($this->request->get['category_id'])) {
        $url .= '&category_id=' . $this->request->get['category_id'];
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
          'href' => $this->url->link('product/search', $url . '&limit=' . $value)
        );
      }

      $url = '';

      if (isset($this->request->get['q'])) {
        $url .= '&q=' . urlencode(html_entity_decode($this->request->get['q'], ENT_QUOTES, 'UTF-8'));
      }

      if (isset($this->request->get['sort'])) {
        $url .= '&sort=' . $this->request->get['sort'];
      }

      if (isset($this->request->get['category_id'])) {
        $url .= '&category_id=' . $this->request->get['category_id'];
      }

      if (isset($this->request->get['order'])) {
        $url .= '&order=' . $this->request->get['order'];
      }

      if (isset($this->request->get['limit'])) {
        $url .= '&limit=' . $this->request->get['limit'];
      }

      $pagination = new Pagination();
      $pagination->total = $product_total;
      $pagination->page = $page;
      $pagination->limit = $limit;
      $pagination->url = $this->url->link('product/search', $url . '&page={page}');

      $data['pagination'] = $pagination->render();
      $data['page'] = $page;
      $data['total_pages'] = ceil($product_total / $limit);

      $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

      if ($page > 1){
        $data['heading_title'] = $data['heading_title'] . " | сторінка " . $page;
        $this->document->setTitle($this->document->getTitle() . " | сторінка " . $page);
      }


      if (isset($this->request->get['q']) && $this->config->get('config_customer_search')) {
        $this->load->model('account/search');

        if ($this->customer->isLogged()) {
          $customer_id = $this->customer->getId();
        } else {
          $customer_id = 0;
        }

        if (isset($this->request->server['REMOTE_ADDR'])) {
          $ip = $this->request->server['REMOTE_ADDR'];
        } else {
          $ip = '';
        }

        $search_data = array(
          'keyword' => $search,
          'products' => $product_total,
          'customer_id' => $customer_id,
          'ip' => $ip
        );

        $this->model_account_search->addSearch($search_data);
      }
    }

    $data['q'] = $search;

    $data['sort'] = $sort;
    $data['order'] = $order;
    $data['limit'] = $limit;

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    if (isset($this->request->get['ajax'])){
      return $this->load->view('product/category_ajax', $data);
    }else {
      $this->response->setOutput($this->load->view('product/search', $data));
    }
  }

  function buildCategoryTree($categories_info) {
    $tree = array();

    foreach ($categories_info as $category_id => $category) {
      $path = explode('_', $category['path']);
      $current_href = $this->url->link('product/category', 'path=' . $category['category_id']);
      $current_level = &$tree;

      $last_id_in_path = end($path); // останній елемент шляху

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

        // Якщо це останній рівень і він вже доданий — не додаємо ще раз
        if ((string)$parent_id === (string)$category_id) {
          break;
        }

        // Переходимо на наступний рівень
        $current_level = &$current_level[$parent_id]['children'];
      }
    }

    return $tree;
  }

}
