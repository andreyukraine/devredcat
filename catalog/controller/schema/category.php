<?php

class ControllerSchemaCategory extends Controller
{
  public function index($data)
  {

    $data['schema_desc'] = '';
    if (isset($data['description'])) {
      $data['schema_desc'] = $data['description'];
    }

    if ($this->session->data['language'] == 'ru-ru') {
      $data['schema_brand'] = "Строительный магазин Первый Дом";
    } else {
      $data['schema_brand'] = "Будівельний магазин Перший Дім";
    }

    $data['schema_url'] = $this->url->link('product/category', 'path=' . $data['category_id']);

    $data['schema_min_price'] = null;
    $data['schema_max_price'] = 0;
    $data['schema_count_item_instock'] = 0;
    $data['schema_rating_count'] = 0;
    $data['schema_rating'] = 0;

    foreach ($data['products'] as &$item){

      if ($item['is_buy']){
        $data['schema_count_item_instock']++;
      }

      $item['thumb'] = $item['images'][0]['thumb'];

      // Конвертуємо ціну у числовий формат
      $price = floatval(preg_replace('/[^0-9.]/', '', $item['price']));
      $special = $item['special'] > 0 ? floatval(preg_replace('/[^0-9.]/', '', $item['special'])) : $price;
      if ($special > 0) {

        // Знаходимо мінімальну ціну, якщо вона ще не визначена, або якщо поточна менша
        if ($data['schema_min_price'] === null || $special < $data['schema_min_price']) {
          $data['schema_min_price'] = $special;
        }
      }

      // Знаходимо мінімальну та максимальну ціну
      $data['schema_max_price'] = max($data['schema_max_price'], $price);

      if (isset($item['rating']) && $item['rating'] > 0 && $item['rating'] <= 5) {
        // Додаємо рейтинг до загальної суми
        $data['schema_rating'] += $item['rating'];

        // Підраховуємо кількість товарів із рейтингом
        $data['schema_rating_count']++;
      }

      unset($item);
    }

    if ($data['schema_min_price'] === null){
      $data['schema_min_price'] = 0;
    }

    // Обчислюємо середній рейтинг, якщо є оцінки
    if ($data['schema_rating_count'] > 0) {
      $data['schema_raiting'] = $data['schema_rating'] / $data['schema_rating_count'];
    } else {
      $data['schema_raiting'] = 0; // Якщо немає рейтингів, середній дорівнює 0
    }

    return $this->load->view('schema/category', $data);
  }
}
