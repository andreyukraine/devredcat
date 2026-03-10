<?php

class ControllerExtensionModuleCarousel extends Controller
{
  public function index($setting)
  {

    $this->load->language('extension/module/occarousel');

    $this->load->model('catalog/manufacturer');
    $this->load->model('tool/image');

    $data = array();

    $data['text_heading_title'] = $this->language->get("text_heading_title");
    if (!empty($setting['name'])){
      $data['text_heading_title'] = $setting['name'];
    }

    $data['text_show_more'] = $this->language->get("text_show_more");
    $data['text_empty'] = $this->language->get("text_empty");

    $data['is_slide'] = true;
    $results = $this->model_catalog_manufacturer->getManufacturers($data);

    foreach ($results as $result) {

      if (isset($result['image'])) {
        $img_brand = !empty($result['image'] && file_exists(DIR_IMAGE . $result['image'])) ? $result['image'] : "no_image.png";
      } else {
        $img_brand = "no_image.png";
      }

      $data['brands'][] = array(
        'name' => $result['name'],
        'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $result['manufacturer_id']),
        'image' => $this->model_tool_image->resize($img_brand, 400, 400)
      );
    }

    $data['module_id'] = rand(1, 1000);

    return $this->load->view('extension/module/carousel', $data);
  }
}
