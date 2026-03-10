<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerInformationCatalog extends Controller
{
  public function index()
  {
    $this->load->language('product/category');

    $this->load->model('catalog/category');
    $this->load->model('catalog/product_status');
    $this->load->model('catalog/product');

    $this->load->model('tool/image');

    $data['text_empty'] = $this->language->get('text_empty');
    $data['text_buy'] = $this->language->get('text_buy');
    $data['is_mobile'] = $this->mobile_detect->isMobile();

    $width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width');
    $height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height');
    if ($data['is_mobile']) {
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

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $category_type = 0;
    if (isset($this->session->data['catalog_type'])) {
      $category_type = (int)$this->session->data['catalog_type'];
    }

    $data['categories'] = array();

    $categories = $this->model_catalog_category->getCategories(0, $category_type);

    foreach ($categories as $category) {

      $category_id = $category['category_id'];
      $data['category_id'] = $category_id;


      $filter_data = array(
        'filter_category_id' => $category_id,
        'filter_sub_category' => true
      );

      $count_prod_cat = $this->model_catalog_product->getTotalProducts($filter_data);

      if ($count_prod_cat > 0) {
        if (!empty($category['image'])) {
          $thumb = $category['image'];
        } else {
          $thumb = 'no_image.png';
        }

        $filter_data_sub = array(
          'filter_category_id' => $category_id,
          'filter_sub_category' => false
        );
        $count_prod_cat = $this->model_catalog_product->getTotalProductsInCategory($filter_data_sub);
        if ($count_prod_cat > 0) {
          $data['categories'][] = array(
            'thumb' => $this->model_tool_image->resize($thumb, 250, 250),
            'category_id' => $category_id,
            'name' => $category['name'],
            'href' => $this->url->link('product/category', $category_id, true)
          );
        }
      }
    }

    $data['continue'] = $this->url->link('common/home');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->response->setOutput($this->load->view('product/category_parent', $data));

  }
}
