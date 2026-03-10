<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountWishList extends Controller {

  public function index() {
    if (!$this->customer->isLogged()) {
      $this->session->data['redirect'] = $this->url->link('account/wishlist', '', true);

      $this->response->redirect($this->url->link('account/login', '', true));
    }

    $this->load->language('account/wishlist');
    $this->load->model('tool/image');
    $this->load->model('account/wishlist');
    $this->load->model('catalog/product');

    if (isset($this->request->get['remove'])) {
      // Remove Wishlist
      $this->model_account_wishlist->deleteWishlist($this->request->get['remove']);

      $this->session->data['success'] = $this->language->get('text_remove');

      $this->response->redirect($this->url->link('account/wishlist'));
    }

    $this->document->setTitle($this->language->get('heading_title'));

    $data['text_empty'] = $this->language->get('text_empty');
    $data['img_empty'] = $this->model_tool_image->resize("no_image.png", 400, 400);
    $data['continue'] = $this->url->link('common/home');


    $data['heading_title'] = $this->language->get('heading_title');
    $data['text_option'] = $this->language->get('text_option');

    $this->document->setRobots('noindex,follow');

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_account'),
      'href' => $this->url->link('account/account', '', true)
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('account/wishlist')
    );

    if (isset($this->session->data['success'])) {
      $data['success'] = $this->session->data['success'];

      unset($this->session->data['success']);
    } else {
      $data['success'] = '';
    }

    $data['products'] = array();

    $results = $this->model_account_wishlist->getWishlist();

    foreach ($results as $result) {

      $product_info = $this->model_catalog_product->getProduct($result['product_id']);

      if ($product_info) {

        $sort_order = "ASC";
        if (isset($this->request->get['order'])) {
          $sort_order = $this->request->get['order'];
        }

        $product_data = array(
          'item' => $product_info,
          'sort_order' => $sort_order
        );
        $options = $this->load->controller('product/options', $product_data);

        $data['products'][] = array(
          'product_id'    => $product_info['product_id'],
          'images'        => $options['images'],
          'options'       => $options['prod_options'],
          'name'          => (utf8_strlen($product_info['name']) > 60 ? utf8_substr($product_info['name'], 0, 60) . '..' : $product_info['name']),
          'price'         => $this->currency->format($options['price'], $this->session->data['currency']),
          'special'       => $options['special'] > 0 ? $this->currency->format($options['special'], $this->session->data['currency']) : false,
          'rate_special'  => $options['percent'],
          'is_buy'        => $options['is_buy'],
          'quantity'      => $options['quantity'],
          'rating'        => (int)$product_info['rating'],
          'uniq_id'       => $options['uniq_id'],
          'cart_id'       => $options['cart_id'],
          'in_cart'       => $options['in_cart'],
          'in_wishlist'   => $options['in_wishlist'] ? 1 : 0,
          'on_stock'      => $options['on_stock'],
          'statuses'      => $options['statuses'],
          'href'          => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
        );
      } else {
        $this->model_account_wishlist->deleteWishlist($result['product_id']);
      }
    }

    $data['menu'] = $this->load->controller('account/menu');

    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->response->setOutput($this->load->view('account/wishlist', $data));
  }

  public function top()
  {
    $data['redirect'] = $this->url->link('account/wishlist', '', true);

    // Totals
    $this->load->model('setting/extension');

    $data['logged'] = $this->customer->isLogged();

    if ($data['logged']) {
      $data['text_items'] = $this->model_account_wishlist->getTotalWishlist();
    }else{
      $data['text_items'] = isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0;
    }

    return $this->load->view('common/wishlist_top', $data);
  }

	public function add() {
		$this->load->language('account/wishlist');

    $this->load->model('catalog/product');

		$json = array();

		if (isset($this->request->post['product_id'])) {
			$product_id = $this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			if ($this->customer->isLogged()) {
				// Edit customers cart
				$this->load->model('account/wishlist');

				$this->model_account_wishlist->addWishlist($this->request->post['product_id']);

				$json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist'));
				$json['total'] = $this->model_account_wishlist->getTotalWishlist();
			} else {
				$json['error'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist'));
				$json['total'] = isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

  public function remove() {
    $this->load->language('account/wishlist');

    $json = array();

    if (isset($this->request->post['product_id'])) {
      $product_id = $this->request->post['product_id'];
    } else {
      $product_id = 0;
    }

    $this->load->model('catalog/product');

    $product_info = $this->model_catalog_product->getProduct($product_id);

    if ($product_info) {
      if ($this->customer->isLogged()) {
        // Edit customers cart
        $this->load->model('account/wishlist');

        $this->model_account_wishlist->deleteWishlist($this->request->post['product_id']);

        $json['success'] = sprintf($this->language->get('text_remove_success'), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist'));

        $json['total'] = $this->model_account_wishlist->getTotalWishlist();
      } else {
        $json['error'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist'));

        $json['total'] = isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0;
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }
}
