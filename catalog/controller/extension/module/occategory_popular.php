<?php

class ControllerExtensionModuleOccategoryPopular extends Controller
{
  public function index($setting)
  {
    $this->load->language('extension/module/occategory_popular');

    $this->load->model('extension/module/occategory_popular');

    $data['categories'] = array();

    $categories = $this->model_extension_module_occategory_popular->getCalegoryList();
    if (!empty($categories)) {
      foreach ($categories as $key => $category) {

        $this->load->model('catalog/category');
        $category_info = $this->model_catalog_category->getCategory((int)$category['category_id']);
        if ($category_info) {

          if (isset($category_info['image'])) {
            $image = $this->model_tool_image->resize($category_info['image'], 400, 400);
          } else {
            $image = $this->model_tool_image->resize("no_image.png", 400, 400);
          }

          $data['categories'][] = array(
            'category_popular_id' => $category['category_popular_id'],
            'href' => $this->url->link('product/category', 'path=' . $category['category_id']),
            "name" => $category_info['name'],
            "image" => $image
          );
        }
      }
    }

    $data['heading_title'] = $setting['name'];
    $data['module_id'] = rand(1, 1000);

    return $this->load->view('extension/module/occategory_popular', $data);
  }
}
