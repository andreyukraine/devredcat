<?php

class ControllerSchemaProduct extends Controller
{
  public function index($data)
  {

    $data['schema_desc'] = '';
    if (isset($data['description'])) {
      $data['schema_desc'] = $this->removeTablesFromHtml($data['description']);
    }

    if (!empty($data["manufacturer"])){
      $data['schema_brand'] = $data["manufacturer"];
    }

    $url = '';
    $data['schema_url'] = $this->url->link('product/product', $url . '&product_id=' . $this->request->get['product_id']);


    $data['schema_price'] = 0;
    $price = floatval(preg_replace('/[^0-9.]/', '', $data['price']));
    $special = ($data['special'] > 0) ? floatval(preg_replace('/[^0-9.]/', '', $data['special'])) : $price;
    $data['schema_price'] = $special;

    return $this->load->view('schema/product', $data);
  }

  function removeTablesFromHtml($html) {
    // Регулярний вираз для видалення всіх таблиць разом із вмістом
    $pattern = '/<table\b[^>]*>.*?<\/table>/is';

    // Видалення таблиць
    $cleanedHtml = preg_replace($pattern, '', $html);

    return $this->cleanHtml($cleanedHtml);
  }

  function cleanHtml($html) {
    // Регулярні вирази для видалення таблиць, стилів і тегів <hr>
    $patterns = [
      '/<table\b[^>]*>.*?<\/table>/is', // Видалення таблиць разом із вмістом
      '/<style\b[^>]*>.*?<\/style>/is', // Видалення стилів
      '/<hr\b[^>]*>/i' // Видалення тегів <hr> з атрибутами
    ];

    // Видаляємо всі вказані елементи
    $cleanedHtml = preg_replace($patterns, '', $html);

    return $cleanedHtml;
  }
}
