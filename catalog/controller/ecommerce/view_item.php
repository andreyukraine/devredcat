<?php

class ControllerEcommerceViewItem extends Controller
{
  public function index($data)
  {

    $data['item_category'] = "";
    $data['item_category2'] = "";
    $data['item_category3'] = "";

    foreach ($data['breadcrumbs'] as $index => $cat){
      if ($index > 0) {
        if ($index == 1) {
          $data['item_category'] = html_entity_decode($cat['text'], ENT_QUOTES, 'UTF-8');
        }
        if ($index == 2) {
          $data['item_category2'] = html_entity_decode($cat['text'], ENT_QUOTES, 'UTF-8');
        }
        if ($index == 3) {
          $data['item_category3'] = html_entity_decode($cat['text'], ENT_QUOTES, 'UTF-8');
        }
      }
    }

    $data['select_opt'] = "";
    foreach ($data['options'] as $opt){
      if ($opt['selected'] > 0){
        $data['select_opt'] = $opt['name'];
        break;
      }
    }

    if ((int)$data['price'] > 0) {
      $data['price'] = substr($data['price'], 0, -3);
    }

    $data['item_name'] = $data['heading_title'];
    $data['item_id'] = $data['product_id'];
    $data['item_brand'] = $data['manufacturer'];

    return $this->load->view('ecommerce/view_item', $data);
  }
}
