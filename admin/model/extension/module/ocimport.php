<?php

class ModelExtensionModuleOcimport extends Model
{

  private $LANG_ID = 15;
  private $STORE_ID = 0;
  private $STORE_COUNT = 0;
  private $PRODUCT_COUNT = 0;
  private $CRON = 0;
  private $separator = ',';

  private $STATUS_FILE = DIR . 'crons/cron_status.txt';

  public function cron_prods()
  {

    $this->getLanguageId($this->config->get('config_language'));
    $this->CRON = 1;

    try {
      $this->importProducts();
    } catch (Exception $e) {
      file_put_contents($this->STATUS_FILE, "Помилка: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
      return false;
    }

  }

  public function cron_cards()
  {

    $this->getLanguageId($this->config->get('config_language'));
    $this->CRON = 1;

    try {
      file_put_contents($this->STATUS_FILE, "load_products\n", LOCK_EX);

      $this->importCards();
      file_put_contents($this->STATUS_FILE, "completed\n", LOCK_EX);

    } catch (Exception $e) {
      file_put_contents($this->STATUS_FILE, "Помилка: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
      return false;
    }
  }

  public function importing($data)
  {

    $this->getLanguageId($this->config->get('config_language'));

    switch ($data->post['type']) {
      case "category":
        $this->importCategories();
        break;
      case "warehouses":
        $this->importWarehouses();
        break;
      case "products":
        $this->importProducts();
        break;
      case "pdo_srvsql":
        $this->importPdo();
        break;
    }

  }

  public function importPdo()
  {

    $serverName = "31.43.188.107,65001";
    $connectionOptions = [
      "Database" => "smarket",
      "Uid" => "bitrix",
      "PWD" => "LoadDataBitrix242424"
    ];

    try {
      $conn = new PDO("sqlsrv:server=$serverName;Database=" . $connectionOptions['Database'], $connectionOptions['Uid'], $connectionOptions['PWD']);
      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      echo "Connection successful!";
    } catch (PDOException $e) {
      echo "Connection failed: " . $e->getMessage();
    }
    echo 2;
  }

  public function importBrandImg()
  {
    $filename = DIR . 'image/catalog/find-images/brands.txt';

    // Проверяем, существует ли папка, и создаем ее, если нет
    if (!file_exists(dirname($filename))) {
      mkdir(dirname($filename), 0755, true);
    }
    // Очищаем файл, если он существует
    if (file_exists($filename)) {
      file_put_contents($filename, ''); // очищает файл
    }

    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "manufacturer");
    if ($query->rows) {
      foreach ($query->rows as $row) {
        if (empty($row['image'])) {
          $dir = DIR_IMAGE . "/catalog/brand";
          $files = array_diff(scandir($dir), ['.', '..']);
          foreach ($files as $file) {
            $name_file = explode(".", $file)[0];
            //якщо це додаткове зображення
            if ($row['guid'] == $name_file) {
              $this->db->query("UPDATE " . DB_PREFIX . "manufacturer SET image = '" . $this->db->escape("catalog/brand/" . $file) . "' WHERE guid = '" . $row['guid'] . "'");
              break;
            }
          }
          $text = $row['name'] . ":" . $row['guid'];
          // Записываем значение в файл с новой строки
          file_put_contents($filename, $text . PHP_EOL, FILE_APPEND);
        }
      }
    }
  }

  public function importCategoryImg()
  {
    $filename = DIR . 'image/catalog/find-images/categories.txt';

    // Проверяем, существует ли папка, и создаем ее, если нет
    if (!file_exists(dirname($filename))) {
      mkdir(dirname($filename), 0755, true);
    }
    // Очищаем файл, если он существует
    if (file_exists($filename)) {
      file_put_contents($filename, ''); // очищает файл
    }

    $query = $this->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = c.category_id) WHERE cd.language_id = '" . (int)$this->LANG_ID . "'");
    if ($query->rows) {
      foreach ($query->rows as $row) {
        if (empty($row['image'])) {
          $dir = DIR_IMAGE . "/catalog/category-image";
          $files = array_diff(scandir($dir), ['.', '..']);
          foreach ($files as $file) {
            $name_file = explode(".", $file)[0];
            //якщо це додаткове зображення
            if ($row['guid'] == $name_file) {
              $this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape("catalog/category-image/" . $file) . "' WHERE guid = '" . $row['guid'] . "'");
              break;
            }
          }

          $path = $this->getCategoryPaths($row['category_id']);
          $cats_ids = explode("_", $path);

          $text = "";
          foreach ($cats_ids as $c_id) {
            $cat_db = $this->getCategory($c_id);
            $text .= $cat_db['name'] . "->";
          }

          $text = $text . ":" . $row['guid'];
          // Записываем значение в файл с новой строки
          file_put_contents($filename, $text . PHP_EOL, FILE_APPEND);
        }
      }
    }
  }

  public function importProductImg()
  {

    $filename = DIR . 'image/catalog/find-images/products.txt';

    // Проверяем, существует ли папка, и создаем ее, если нет
    if (!file_exists(dirname($filename))) {
      mkdir(dirname($filename), 0755, true);
    }
    // Очищаем файл, если он существует
    if (file_exists($filename)) {
      file_put_contents($filename, ''); // очищает файл
    }

    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product_quantity GROUP BY ean");
    if ($query->rows) {
      foreach ($query->rows as $key => $row) {

        $s = json_decode($row["options_value"]);

        $opt_val = "";
        foreach ($s as $product_option_value_id) {
          $opt_val_row = $this->db->query("SELECT ovd.name as name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN `" . DB_PREFIX . "option_value_description` ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . $product_option_value_id . "' AND ovd.language_id = '" . (int)$this->LANG_ID . "'");
          if ($opt_val_row->rows > 0) {
            $opt_val .= " (" . $opt_val_row->row['name'] . ") ";
          }
        }

        if (!empty(trim($row['ean']))) {
          $dir = DIR_IMAGE . "/catalog/product";
          $files = array_diff(scandir($dir), ['.', '..']);
          $is_find = false;
          foreach ($files as $file) {
            $name_file = explode(".", $file)[0];

            if ($row['ean'] == $name_file) {

              if (!empty($s)) {
                foreach ($s as $product_option_value_id) {
                  $this->query("UPDATE " . DB_PREFIX . "product_option_value SET image_opt = '" . $this->db->escape("catalog/product/" . $file) . "' WHERE product_option_value_id = '" . (int)$product_option_value_id . "' AND product_id = '" . (int)$row['product_id'] . "'");
                }
              } else {
                $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape("catalog/product/" . $file) . "' WHERE product_id = '" . $row['product_id'] . "'");
              }
              $is_find = true;
              break;
            }
          }

          if (!$is_find) {
            $prod_db = $this->getProduct($row['product_id']);
            if (!empty($prod_db)) {
              $prod_name = $prod_db['name'];
            }
            $text = $prod_name;
            if (!empty($opt_val)) {
              $text .= " - " . $opt_val;
            }
            $text .= " : " . $row['ean'];
            // Записываем значение в файл с новой строки
            file_put_contents($filename, $text . PHP_EOL, FILE_APPEND);
          }
        }
      }
    }
  }


  public function importMetaCategoryExcel($filepath)
  {

    // Основні файли PHPExcel
    require_once DIR_SYSTEM . 'library/PHPExcel.php';
    require_once DIR_SYSTEM . 'library/PHPExcel/Writer/Excel5.php';
    require_once DIR_SYSTEM . 'library/PHPExcel/IOFactory.php';

    // Load the spreadsheet file
    $spreadsheet = PHPExcel_IOFactory::load($filepath);

    // Select the first worksheet
    $worksheet = $spreadsheet->getActiveSheet();

    // Get the highest row and column numbers
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();

    $col_code = 0;
    $col_h1 = 0;
    $col_title = 0;
    $col_desc = 0;

    //остання буква столбца
    $highestColumnLetter = $worksheet->getHighestColumn();
    // Перетворюємо букву стовпця в номер
    $highestColumnNumber = PHPExcel_Cell::columnIndexFromString($highestColumnLetter);

    for ($row = 1; $row <= $highestRow; $row++) {
      for ($col = 0; $col <= $highestColumnNumber; $col++) {
        $col_name = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
        if ($col_name === $this->request->post['h1']) {
          $col_h1 = $col;
        } else if ($col_name === $this->request->post['title']) {
          $col_title = $col;
        } else if ($col_name === $this->request->post['desc']) {
          $col_desc = $col;
        } else if ($col_name === $this->request->post['code']) {
          $col_code = $col;
        }
      }
      break;
    }
    // Iterate through each row of the worksheet
    for ($row = 2; $row <= $highestRow; $row++) {

      $code = (string)$worksheet->getCellByColumnAndRow($col_code, $row)->getValue();
      if ($code == null) {
        continue;
      }

      $h1 = $worksheet->getCellByColumnAndRow($col_h1, $row)->getValue();
      $title = $worksheet->getCellByColumnAndRow($col_title, $row)->getValue();
      $desc = $worksheet->getCellByColumnAndRow($col_desc, $row)->getValue();

      $category_info = $this->getCategoryByUid($code);
      if ($category_info != null) {
        $this->query("UPDATE `" . DB_PREFIX . "category_description` SET
          meta_h1 ='" . $this->db->escape($h1) . "',meta_title ='" . $this->db->escape($title) . "',
          meta_description = '" . $this->db->escape($desc) . "'
          WHERE `category_id` = '" . (int)$category_info['category_id'] . "' AND language_id = '" . (int)$this->LANG_ID . "'");
      }
    }
  }

  /**
   * Імпорт з CSV експорту WooCommerce: категорії, товари (variable/variation), атрибути, опції, ціни.
   * Використовує addCategory та addProduct моделі.
   *
   * @param string $filepath шлях до CSV файлу
   * @return array ['success' => string] або ['error' => string]
   */
  public function importFromWooCommerceCsv($filepath, $offset = 0, $limit = 0)
  {
    $this->getLanguageId($this->config->get('config_language'));

    if (!is_readable($filepath)) {
      return array('error' => 'Файл не читається: ' . $filepath);
    }

    $handle = fopen($filepath, 'r');
    if ($handle === false) {
      return array('error' => 'Не вдалося відкрити CSV');
    }

    // UTF-8 BOM
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
      rewind($handle);
    }

    $header = fgetcsv($handle, 0, ',', '"', '');
    if ($header === false || empty($header)) {
      fclose($handle);
      return array('error' => 'Порожній або некоректний CSV');
    }

    $header = array_map('trim', $header);
    if (isset($header[0]) && substr($header[0], 0, 3) === "\xEF\xBB\xBF") {
      $header[0] = substr($header[0], 3);
    }
    $col = array();
    foreach ($header as $i => $h) {
      $col[$h] = $i;
    }

    $hasType = isset($col['Тип']);
    $hasCategories = isset($col['Категорії']);
    $hasName = isset($col['Назва']);
    if (!$hasName || !$hasType) {
      fclose($handle);
      return array('error' => 'У CSV мають бути стовпці: Тип, Назва.');
    }

    $rows = array();
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
      if (count($row) < count($header)) {
        $row = array_pad($row, count($header), '');
      }
      $rows[] = $row;
    }
    fclose($handle);

    $categoriesCreated = 0;
    $productsCreated = 0;

    // 1) Групуємо товари: variable = батьківський, variation = варіанти
    $parentRows = array();
    $variationsByParent = array();
    foreach ($rows as $row) {
      $type = isset($row[$col['Тип']]) ? trim($row[$col['Тип']]) : '';
      if ($type === 'variable') {
        $parentRows[] = $row;
      } elseif ($type === 'variation') {
        $predok = (isset($col['Предок']) && isset($row[$col['Предок']])) ? trim($row[$col['Предок']]) : '';
        if (preg_match('/id\s*:\s*(\d+)/i', $predok, $m)) {
          $pId = (int)$m[1];
          if (!isset($variationsByParent[$pId])) $variationsByParent[$pId] = array();
          $variationsByParent[$pId][] = $row;
        }
      }
    }

    $totalProducts = count($parentRows);

    // Якщо offset == 0, імпортуємо категорії
    if ($offset == 0 && $hasCategories) {
      $allPaths = array();
      foreach ($rows as $row) {
        $catStr = isset($row[$col['Категорії']]) ? trim($row[$col['Категорії']]) : '';
        if ($catStr === '') continue;
        $parts = preg_split('/\s*,\s*/u', $catStr);
        foreach ($parts as $pathStr) {
          $pathStr = trim($pathStr);
          if ($pathStr === '') continue;
          $segments = array_map('trim', explode('>', $pathStr));
          $segments = array_filter($segments);
          if (empty($segments)) continue;
          $fullPath = implode(' > ', $segments);
          $allPaths[$fullPath] = $segments;
        }
      }
      ksort($allPaths);

      $createdGuids = array();
      foreach ($allPaths as $fullPath => $segments) {
        $guid = 'csv_' . md5($fullPath);
        if (isset($createdGuids[$guid])) continue;
        $parentGuids = array();
        for ($i = 0; $i < count($segments) - 1; $i++) {
          $parentPath = implode(' > ', array_slice($segments, 0, $i + 1));
          $parentGuids[] = 'csv_' . md5($parentPath);
        }
        $name = end($segments);
        $level = count($segments);
        $catData = array(
          'id' => $guid, 'name' => $name, 'level' => $level, 'child_categories' => $parentGuids,
          'type' => 0, 'description' => '', 'meta_title' => $name, 'meta_description' => '', 'meta_keyword' => ''
        );
        $existing = $this->getCategoryByUid($guid);
        if (empty($existing)) {
          $this->addCategory($catData);
          $categoriesCreated++;
        }
        $createdGuids[$guid] = true;
      }
    }

    // Склад
    $warehouses = $this->getWarehouses();
    $warehouse_id = !empty($warehouses) ? (int)$warehouses[0]['warehouse_id'] : 0;
    if ($warehouse_id <= 0) {
      return array('error' => 'Додайте склад для імпорту товарів.');
    }

    $dir_product = DIR_IMAGE . 'catalog/product/';
    if (!is_dir($dir_product)) mkdir($dir_product, 0755, true);

    // Зріз товарів
    if ($limit > 0) {
      $parentRowsSlice = array_slice($parentRows, $offset, $limit);
    } else {
      $parentRowsSlice = $parentRows;
    }

    foreach ($parentRowsSlice as $row) {
      $parentId = isset($row[$col['ID']]) ? (int)$row[$col['ID']] : 0;
      $name = isset($row[$col['Назва']]) ? trim($row[$col['Назва']]) : '';
      if ($name === '') continue;

      $variations = isset($variationsByParent[$parentId]) ? $variationsByParent[$parentId] : array();
      
      $catId = null;
      if ($hasCategories && isset($row[$col['Категорії']])) {
        $catStr = trim($row[$col['Категорії']]);
        if ($catStr !== '') {
          $firstPath = trim(explode(',', $catStr)[0]);
          if ($firstPath !== '') $catId = 'csv_' . md5($firstPath);
        }
      }

      $brand = isset($col['Бренди']) && isset($row[$col['Бренди']]) ? trim($row[$col['Бренди']]) : '';
      $shortDesc = isset($col['Короткий опис']) && isset($row[$col['Короткий опис']]) ? trim($row[$col['Короткий опис']]) : '';
      $desc = isset($col['Опис']) && isset($row[$col['Опис']]) ? trim($row[$col['Опис']]) : $shortDesc;

      $opts = array();
      for ($n = 1; $n <= 12; $n++) {
        $nameKey = 'Назва ' . $n . ' атрибуту';
        $valKey = $n . ' значення атрибуту';
        if (!isset($col[$nameKey]) || !isset($col[$valKey])) continue;
        $attrName = trim($row[$col[$nameKey]]);
        $attrVal = trim($row[$col[$valKey]]);
        if ($attrName === '' || $attrVal === '') continue;
        $opts[] = array('name' => $attrName, 'value' => $attrVal, 'guid' => 'csv_attr_' . md5($attrName));
      }

      $variants = array();
      if (empty($variations)) {
        $price = 0;
        if (isset($col['Звичайна ціна'])) $price = (float)str_replace(array(',', ' '), array('.', ''), $row[$col['Звичайна ціна']]);
        $priceBase = $price;
        if (isset($col['Ціна зі знижкою'])) {
          $sale = (float)str_replace(array(',', ' '), array('.', ''), $row[$col['Ціна зі знижкою']]);
          if ($sale > 0) $price = $sale;
        }
        $sku = isset($col['Артикул']) ? trim($row[$col['Артикул']]) : ('csv' . $parentId);
        if (isset($col['Зображення']) && !empty($row[$col['Зображення']]) && !empty($sku)) {
          $imgUrls = explode(',', $row[$col['Зображення']]);
          $this->downloadProductImage(trim($imgUrls[0]), $sku);
        }
        $variants[] = array(
          'barcode' => $sku, 'sku' => $sku, 'guid' => 'csv' . $parentId . '_0',
          'price' => $price, 'price_base' => $priceBase,
          'weight' => (isset($col['Вага (кг)']) && isset($row[$col['Вага (кг)']])) ? str_replace(',', '.', $row[$col['Вага (кг)']]) : 0,
          'quantity' => 0, 'unit' => 'шт', 'var' => array()
        );
      } else {
        foreach ($variations as $vIdx => $vRow) {
          $price = 0;
          if (isset($col['Звичайна ціна'])) $price = (float)str_replace(array(',', ' '), array('.', ''), $vRow[$col['Звичайна ціна']]);
          $priceBase = $price;
          if (isset($col['Ціна зі знижкою'])) {
            $sale = (float)str_replace(array(',', ' '), array('.', ''), $vRow[$col['Ціна зі знижкою']]);
            if ($sale > 0) $price = $sale;
          }
          $sku = isset($col['Артикул']) ? trim($vRow[$col['Артикул']]) : ('csv' . $parentId . '_' . $vIdx);
          if (isset($col['Зображення']) && !empty($vRow[$col['Зображення']]) && !empty($sku)) {
            $vImgUrls = explode(',', $vRow[$col['Зображення']]);
            $this->downloadProductImage(trim($vImgUrls[0]), $sku);
          }
          $optName = 'Вага'; $optVal = '';
          if (isset($col['Вага (кг)'])) $optVal = trim($vRow[$col['Вага (кг)']]);
          if ($optVal === '' && isset($col['2 значення атрибуту']) && isset($vRow[$col['2 значення атрибуту']])) {
            $optVal = trim($vRow[$col['2 значення атрибуту']]);
            if (isset($col['Назва 2 атрибуту']) && isset($vRow[$col['Назва 2 атрибуту']])) {
              $optName = trim($vRow[$col['Назва 2 атрибуту']]); if ($optName === '') $optName = 'Вага';
            }
          }
          if ($optVal === '' && isset($col['1 значення атрибуту']) && isset($vRow[$col['1 значення атрибуту']])) {
            $optVal = trim($vRow[$col['1 значення атрибуту']]);
            if (isset($col['Назва 1 атрибуту']) && isset($vRow[$col['Назва 1 атрибуту']])) {
              $optName = trim($vRow[$col['Назва 1 атрибуту']]); if ($optName === '') $optName = 'Варіант';
            }
          }
          $var = array();
          if ($optVal !== '') $var[] = array('name' => $optName, 'value' => $optVal, 'id' => $sku, 'description' => '', 'composition' => '');
          $variants[] = array(
            'barcode' => $sku, 'sku' => $sku, 'guid' => $sku, 'price' => $price, 'price_base' => $priceBase,
            'weight' => (isset($col['Вага (кг)']) && isset($vRow[$col['Вага (кг)']])) ? str_replace(',', '.', $vRow[$col['Вага (кг)']]) : 0,
            'quantity' => 0, 'unit' => 'шт', 'var' => $var
          );
        }
      }

      $brandData = array();
      if ($brand !== '') $brandData[] = array('name' => $brand, 'guid' => 'csv_brand_' . md5($brand));

      $productData = array(
        'name' => $name, 'description' => $desc, 'id' => 'csv_prod_' . $parentId,
        'code' => 'csv' . $parentId, 'catId' => $catId, 'brand' => $brandData,
        'opts' => $opts, 'variants' => $variants
      );

      try {
        $existing = $this->getProductByUid($productData['id']);
        if (!empty($existing)) {
          $this->updateProduct($warehouse_id, $existing, $productData);
        } else {
          $this->addProduct($warehouse_id, $productData);
        }
        $productsCreated++;
      } catch (Exception $e) {}
    }

    return array(
      'success' => 'Оброблено ' . ($offset + count($parentRowsSlice)) . ' з ' . $totalProducts . ' товарів.',
      'total' => $totalProducts,
      'imported' => $offset + count($parentRowsSlice),
      'done' => ($offset + count($parentRowsSlice)) >= $totalProducts
    );
  }

  public function cropProductImg($filter_data, $img_name = "")
  {
    $dir_image = DIR_IMAGE;
    $dir_catalog_product = DIR_IMAGE . "catalog/product";

    if (!empty($img_name)) {
      // Обробка конкретного зображення за назвою файлу
      $files = array_diff(scandir($dir_catalog_product), ['.', '..']);
      foreach ($files as $file) {
        $name_file = explode(".", $file)[0];
        if ($name_file == $img_name) {
          try {
            $header = "load_products\nОбробка зображення: {$img_name}";
            file_put_contents($this->STATUS_FILE, $header . "\n", LOCK_EX);

            $this->autoCropImage($dir_catalog_product . "/" . $file, $dir_catalog_product . "/" . $file);
          } catch (Exception $e) {
          }
        }
      }
    } else if (!empty($filter_data)) {

      if ($filter_data['filter_category'] > 0) {

        // Обробка товарів за фільтром (категорією)
        $sql = "SELECT p.product_id, p.image, p.ean FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)";

        if (isset($filter_data['filter_category']) && !is_null($filter_data['filter_category'])) {
          $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)";
        }

        $sql .= " WHERE pd.language_id = '" . (int)($this->config->get('config_language_id') ?: $this->getLanguageId($this->config->get('config_language'))) . "'";

        if (isset($filter_data['filter_category']) && !is_null($filter_data['filter_category'])) {
          if (!empty($filter_data['filter_category']) && !empty($filter_data['filter_sub_category'])) {
            $this->load->model('catalog/category');
            $categories = $this->model_catalog_category->getCategoriesChildren($filter_data['filter_category']);
            $implode_data = array();
            foreach ($categories as $category) {
              $implode_data[] = "p2c.category_id = '" . (int)$category['category_id'] . "'";
            }
            if ($implode_data) {
              $sql .= " AND (" . implode(' OR ', $implode_data) . ")";
            }
          } else {
            if ((int)$filter_data['filter_category'] > 0) {
              $sql .= " AND p2c.category_id = '" . (int)$filter_data['filter_category'] . "'";
            } else {
              $sql .= " AND p2c.category_id IS NULL";
            }
          }
        }

        $sql .= " GROUP BY p.product_id";

        $query = $this->db->query($sql);
        $products = $query->rows;
        $count_products = count($products);

        $product_processed_count = 1;

        foreach ($products as $product) {
          $product_id = $product['product_id'];
          $ean = !empty($product['ean']) ? $product['ean'] : "ID: " . $product_id;

          $header = "load_products\nШк: {$ean} - {$product_processed_count} з {$count_products}";
          file_put_contents($this->STATUS_FILE, $header . "\n", LOCK_EX);

          // 1. Обробка головного зображення
          if (!empty($product['image']) && is_file($dir_image . $product['image'])) {
            try {
              $this->autoCropImage($dir_image . $product['image'], $dir_image . $product['image']);
            } catch (Exception $e) {
            }
          }

          // 2. Обробка додаткових зображень
//          $images_query = $this->db->query("SELECT image FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");
//          foreach ($images_query->rows as $extra_image) {
//            if (!empty($extra_image['image']) && is_file($dir_image . $extra_image['image'])) {
//              try {
//                $this->autoCropImage($dir_image . $extra_image['image'], $dir_image . $extra_image['image']);
//              } catch (Exception $e) {
//              }
//            }
//          }

          $product_processed_count++;
        }
      }
    } else {
      // Обробка всіх зображень у папці (якщо немає ні імені, ні фільтра)
//      $files = array_diff(scandir($dir_catalog_product), ['.', '..']);
//      $count_files = count($files);
//      $i = 1;
//      foreach ($files as $file) {
//        if (count(explode("_", $file)) > 1) {
//          $i++;
//          continue;
//        }
//
//        $name_file = explode(".", $file)[0];
//        $header = "load_products\nЗображення: {$name_file} - {$i} з {$count_files}";
//        file_put_contents($this->STATUS_FILE, $header . "\n", LOCK_EX);
//
//        try {
//          $this->autoCropImage($dir_catalog_product . "/" . $file, $dir_catalog_product . "/" . $file);
//        } catch (Exception $e) {
//        }
//        $i++;
//      }
    }

    file_put_contents($this->STATUS_FILE, "completed", LOCK_EX);
  }


//  function autoCropImage($sourcePath, $outputPath, $threshold = 240) {
//    $image = imagecreatefromjpeg($sourcePath);
//    $width = imagesx($image);
//    $height = imagesy($image);
//
//    $top = $height;
//    $bottom = 0;
//    $left = $width;
//    $right = 0;
//
//    for ($y = 0; $y < $height; $y++) {
//      for ($x = 0; $x < $width; $x++) {
//        $color = imagecolorat($image, $x, $y);
//        $rgb = imagecolorsforindex($image, $color);
//
//        // Проверяем, является ли пиксель "не белым"
//        if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
//          if ($x < $left) $left = $x;
//          if ($x > $right) $right = $x;
//          if ($y < $top) $top = $y;
//          if ($y > $bottom) $bottom = $y;
//        }
//      }
//    }
//
//    // Если не найдено небелых пикселей
//    if ($left > $right || $top > $bottom) {
//      copy($sourcePath, $outputPath);
//      return true;
//    }
//
//    $newWidth = $right - $left + 1;
//    $newHeight = $bottom - $top + 1;
//    $cropped = imagecreatetruecolor($newWidth, $newHeight);
//
//    //без покращення просто копіюємо
//    //imagecopy($cropped, $image, 0, 0, $left, $top, $newWidth, $newHeight);
//
//    //для покращення зображення
//    imagecopyresampled(
//      $cropped, $image,
//      0, 0, $left, $top,
//      $newWidth, $newHeight, $newWidth, $newHeight
//    );
//
//    imagejpeg($cropped, $outputPath, 90);
//    imagedestroy($image);
//    imagedestroy($cropped);
//
//    return true;
//  }

  function autoCropImage($sourcePath, $outputPath, $threshold = 240)
  {
    $image = imagecreatefromjpeg($sourcePath);
    $width = imagesx($image);
    $height = imagesy($image);

    $top = $height;
    $bottom = 0;
    $left = $width;
    $right = 0;

    // Анализ границ
    for ($y = 0; $y < $height; $y++) {
      for ($x = 0; $x < $width; $x++) {
        $color = imagecolorat($image, $x, $y);
        $rgb = imagecolorsforindex($image, $color);
        if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
          if ($x < $left) $left = $x;
          if ($x > $right) $right = $x;
          if ($y < $top) $top = $y;
          if ($y > $bottom) $bottom = $y;
        }
      }
    }

    // Если вся картинка "белая", вернуть оригинал
    if ($left >= $right || $top >= $bottom) {
      copy($sourcePath, $outputPath);
      return true;
    }

    $newWidth = $right - $left + 1;
    $newHeight = $bottom - $top + 1;
    $cropped = imagecreatetruecolor($newWidth, $newHeight);

    // Настройка качества
    if (function_exists('imagesetinterpolation')) {
      imagesetinterpolation($cropped, IMG_BICUBIC);
    }

    // Копирование с интерполяцией
    imagecopyresampled(
      $cropped, $image,
      0, 0, $left, $top,
      $newWidth, $newHeight, $newWidth, $newHeight
    );

    // Опционально: повышение резкости
    $sharpen = [
      [-1, -1, -1],
      [-1, 32, -1],
      [-1, -1, -1]
    ];
    imageconvolution($cropped, $sharpen, 24, 0);

    // Сохранение в максимальном качестве
    imagejpeg($cropped, $outputPath, 95);

    imagedestroy($image);
    imagedestroy($cropped);
    return true;
  }

  public function importDisconts()
  {

    $curl = curl_init();

    $setting_module = $this->getSetting("module_ocimport");

    $login = $setting_module['login'];
    $password = $setting_module['password'];
    // Формируем строку "логин:пароль" и кодируем в base64
    $authString = base64_encode("$login:$password");

    curl_setopt_array($curl, array(
      CURLOPT_URL => $setting_module['url'] . '/disconts',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Basic ' . $authString
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    if (!empty($response)) {
      file_put_contents($this->STATUS_FILE, "load" . "\n");
      $this->saveDisconts($response);
    }
  }

  public function getCategoryPaths($category_id)
  {
    $path = '';
    $category = $this->db->query("SELECT c.`category_id`,c.`parent_id` FROM " . DB_PREFIX . "category c WHERE c.`category_id` = " . (int)$category_id . "");
    if (isset($category->row['parent_id']) && ($category->row['parent_id'] != 0)) {
      $path .= $this->getCategoryPaths($category->row['parent_id']) . '_';
    }
    if (isset($category->row['category_id'])) {
      $path .= $category->row['category_id'];
    }
    return $path;
  }

  private function saveDisconts($response)
  {
    $disconts = json_decode($response, true);
    if (!empty($disconts['disconts'])) {

      $this->clearAllDisconts();

      foreach ($disconts['disconts'] as $key => $discont) {

        $progress = "Дисконт : (ID: {$key})\n";
        file_put_contents($this->STATUS_FILE, $progress, FILE_APPEND | LOCK_EX);

        $customer_group_id = 1;
        $prod = $this->getProductByUid($discont['prod']);
        $prod_qty = $this->getProductQtyByUid($discont['variant']);
        $percent = (int)$discont['percent'];

        // Якщо дати не передані або пусті, встановлюємо '0000-00-00'
        $date_start = !empty($discont['date_start']) ? $this->db->escape($discont['date_start']) : '0000-00-00';
        $date_end = !empty($discont['date_end']) ? $this->db->escape($discont['date_end']) : '0000-00-00';

        // Перевірка на коректність дати (опціонально)
        if ($date_start != '0000-00-00' && !strtotime($date_start)) {
          $date_start = '0000-00-00'; // Якщо дата невалідна
        }
        if ($date_end != '0000-00-00' && !strtotime($date_end)) {
          $date_end = '0000-00-00';
        }

        if (!empty($prod) && !empty($prod_qty) && $percent > 0) {
          $this->db->query("
          INSERT INTO `" . DB_PREFIX . "product_discount` SET 
              priority = 1, 
              percentage = '" . (float)$percent . "', 
              `options` = '" . $this->db->escape($prod_qty['options']) . "', 
              `options_value` = '" . $this->db->escape($prod_qty['options_value']) . "', 
              `date_start` = '" . $date_start . "', 
              `date_end` = '" . $date_end . "', 
              customer_group_id = '" . (int)$customer_group_id . "', 
              product_id = '" . (int)$prod['product_id'] . "'
          ");
        }
      }

      file_put_contents($this->STATUS_FILE, "completed");
    } else {
      file_put_contents($this->STATUS_FILE, "error" . "\n");
      file_put_contents($this->STATUS_FILE, "Помилка: Не отримані дані для завантаження" . "\n", FILE_APPEND | LOCK_EX);
    }

  }

  public function importCards()
  {

    $setting_module = $this->getSetting("module_ocimport");

    $curl = curl_init();

    $login = $setting_module['login'];
    $password = $setting_module['password'];
    // Формируем строку "логин:пароль" и кодируем в base64
    $authString = base64_encode("$login:$password");

    curl_setopt_array($curl, array(
      CURLOPT_URL => $setting_module['url'] . '/cards',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Basic ' . $authString
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    if (!empty($response)) {
      file_put_contents($this->STATUS_FILE, "load" . "\n");
      $this->saveCards($response);
    }
  }


  public function importAddressesClient()
  {
    $setting_module = $this->getSetting("module_ocimport");

    $customers = $this->getCustomers();
    if (!empty($customers)) {
      foreach ($customers as $customer) {
        $curl = curl_init();

        $login = $setting_module['login'];
        $password = $setting_module['password'];
        // Формируем строку "логин:пароль" и кодируем в base64
        $authString = base64_encode("$login:$password");

        curl_setopt_array($curl, array(
          CURLOPT_URL => $setting_module['url'] . '/get-adresses-client/' . $customer['customer_id'],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Basic ' . $authString
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        if (!empty($response)) {
          file_put_contents($this->STATUS_FILE, "load" . "\n");
          $this->saveAddressClient($response, $customer['customer_id']);
        }
      }
    }
  }

  private function saveAddressClient($response, $customer_id)
  {
    $response_mass = json_decode($response, true);

    if (!empty($response_mass['address'])) {
      foreach ($response_mass['address'] as $line) {

        $customer_code_erp = $line['client_code'];
        $customer_name_erp = $line['client_name'];
        $customer_ourdelivery = $line['ourdelivery'];

        $customer_type = 0;
        if (isset($line['customer_type'])) {
          $customer_type = $line['customer_type'];
        }

        $default_address = false;

        if (isset($line['address_car'])) {
          if (!empty($line['address_car'])) {
            foreach ($line['address_car'] as $item_car) {

              $progress = "Адреса автомобіль : (ID: {$item_car['guid']})\n";
              file_put_contents($this->STATUS_FILE, $progress, FILE_APPEND | LOCK_EX);

              $address_id = 0;

              $address_db = $this->getAddressClientByUid($item_car['guid'], $customer_id, $customer_code_erp);
              if (!empty($address_db)) {
                $address_id = $address_db['address_id'];
                $this->updateAddressClient($address_db, $customer_id, $customer_code_erp, "car", $customer_name_erp, $customer_type, $customer_ourdelivery, $item_car);
              } else {
                $address_id = $this->addAddressClient($item_car, $customer_id, $customer_code_erp, "car", $customer_name_erp, $customer_type, $customer_ourdelivery);
              }

              if ($address_id > 0) {
                $default_address = true;
                $this->updateCustomerAdressDefault($customer_id, $address_id);
              }

            }
          }
        }

        if (isset($line['address_np'])) {
          if (!empty($line['address_np'])) {
            foreach ($line['address_np'] as $item_np) {

              $address_id = 0;

              if (isset($item_np['dveri'])) {
                foreach ($item_np['dveri'] as $item_dvery) {

                  $progress = "Адреса нова пошта : (ID: {$item_dvery['guid']})\n";
                  file_put_contents($this->STATUS_FILE, $progress, FILE_APPEND | LOCK_EX);

                  $address_db = $this->getAddressClientByUid($item_dvery['guid'], $customer_id, $customer_code_erp);
                  if (!empty($address_db)) {
                    $address_id = $address_db['address_id'];
                    $this->updateAddressClient($address_db, $customer_id, $customer_code_erp, "np_dveri", $customer_name_erp, $customer_type, $customer_ourdelivery, $item_dvery);
                  } else {
                    $address_id = $this->addAddressClient($item_dvery, $customer_id, $customer_code_erp, "np_dveri", $customer_name_erp, $customer_type, $customer_ourdelivery);
                  }
                }
              }
              if (isset($item_np['posts'])) {
                foreach ($item_np['posts'] as $item_post) {

                  $progress = "Адреса нова пошта : (ID: {$item_post['guid']})\n";
                  file_put_contents($this->STATUS_FILE, $progress, FILE_APPEND | LOCK_EX);

                  $address_db = $this->getAddressClientByUid($item_post['guid'], $customer_id, $customer_code_erp);
                  if (!empty($address_db)) {
                    $address_id = $address_db['address_id'];
                    $this->updateAddressClient($address_db, $customer_id, $customer_code_erp, "np_post", $customer_name_erp, $customer_type, $customer_ourdelivery, $item_post);
                  } else {
                    $address_id = $this->addAddressClient($item_post, $customer_id, $customer_code_erp, "np_post", $customer_name_erp, $customer_type, $customer_ourdelivery);
                  }
                }
              }

              if (!$default_address) {
                if ($address_id > 0) {
                  $default_address = true;
                  $this->updateCustomerAdressDefault($customer_id, $address_id);
                }
              }
            }
          }
        }


      }
      file_put_contents($this->STATUS_FILE, "completed");
    } else {
      file_put_contents($this->STATUS_FILE, "error" . "\n");
      file_put_contents($this->STATUS_FILE, "Помилка: Не отримані дані для завантаження" . "\n", FILE_APPEND | LOCK_EX);
    }
  }

  private function addAddressClient($data, $customer_id, $customer_code_1c, $type, $name_erp, $customer_type, $customer_ourdelivery)
  {

    switch ($type) {
      case "car":
        $this->query("INSERT INTO `" . DB_PREFIX . "address` SET 
        guid = '" . $this->db->escape($data['guid']) . "', 
        customer_id ='" . (int)$customer_id . "', 
        customer_cod_guid ='" . $customer_code_1c . "', 
        customer_type ='" . (int)$customer_type . "', 
        firstname ='" . $this->db->escape($name_erp) . "',
        address_1 ='" . $this->db->escape($data['name']) . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        type='" . $this->db->escape($type) . "'");
        break;
      case "np_post":
        $this->query("INSERT INTO `" . DB_PREFIX . "address` SET 
        guid = '" . $this->db->escape($data['guid']) . "', 
        firstname ='" . $this->db->escape($name_erp) . "',
        customer_id ='" . (int)$customer_id . "', 
        customer_cod_guid ='" . $customer_code_1c . "', 
        customer_type ='" . (int)$customer_type . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        np_city_id ='" . $this->db->escape($data['city_id']) . "',
        np_city_name ='" . $this->db->escape($data['city_name']) . "',
        np_post_id ='" . $this->db->escape($data['post_id']) . "',
        np_post_name ='" . $this->db->escape($data['post_name']) . "',
        np_customer_name ='" . $this->db->escape($data['customer_name']) . "',
        np_customer_lastname ='" . $this->db->escape($data['customer_lastname']) . "', 
        np_customer_phone ='" . $this->db->escape($data['customer_phone']) . "', 
        type='" . $this->db->escape($type) . "'");
        break;
      case "np_dveri":
        $this->query("INSERT INTO `" . DB_PREFIX . "address` SET 
        guid = '" . $this->db->escape($data['guid']) . "', 
        firstname ='" . $this->db->escape($name_erp) . "',
        customer_id ='" . (int)$customer_id . "', 
        customer_cod_guid ='" . $customer_code_1c . "', 
        customer_type ='" . (int)$customer_type . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        np_city_id ='" . $this->db->escape($data['city_id']) . "',
        np_city_name ='" . $this->db->escape($data['city_name']) . "',
        np_street_id ='" . $this->db->escape($data['street_id']) . "',
        np_street_name ='" . $this->db->escape($data['street_name']) . "',
        np_house ='" . $this->db->escape($data['house']) . "',
        np_level ='" . $this->db->escape($data['level']) . "',
        np_apartment ='" . $this->db->escape($data['apartment']) . "',
        np_customer_name ='" . $this->db->escape($data['customer_name']) . "',
        np_customer_lastname ='" . $this->db->escape($data['customer_lastname']) . "', 
        np_customer_phone ='" . $this->db->escape($data['customer_phone']) . "', 
        type='" . $this->db->escape($type) . "'");
        break;
    }
    $id = $this->db->getLastId();
    return $id;
  }

  private function updateAddressClient($address_db, $customer_id, $customer_code_1c, $type, $name_erp, $customer_type, $customer_ourdelivery, $data = array())
  {
    switch ($type) {
      case "car":
        $this->query("UPDATE `" . DB_PREFIX . "address` SET 
        firstname ='" . $this->db->escape($name_erp) . "',
        customer_type ='" . (int)$customer_type . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        address_1 = '" . $this->db->escape($data['name']) . "' 
        WHERE `address_id` = '" . (int)$address_db['address_id'] . "'");
        break;
      case "np_post":
        $this->query("UPDATE `" . DB_PREFIX . "address` SET 
        firstname ='" . $this->db->escape($name_erp) . "',
        customer_type ='" . (int)$customer_type . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        np_city_id ='" . $this->db->escape($data['city_id']) . "',
        np_city_name ='" . $this->db->escape($data['city_name']) . "',
        np_post_id ='" . $this->db->escape($data['post_id']) . "',
        np_post_name ='" . $this->db->escape($data['post_name']) . "',
        np_customer_name ='" . $this->db->escape($data['customer_name']) . "',
        np_customer_lastname ='" . $this->db->escape($data['customer_lastname']) . "', 
        np_customer_phone ='" . $this->db->escape($data['customer_phone']) . "' 
        WHERE `address_id` = '" . (int)$address_db['address_id'] . "'");
        break;
      case "np_dveri":
        $this->query("UPDATE `" . DB_PREFIX . "address` SET 
        firstname ='" . $this->db->escape($name_erp) . "',
        customer_type ='" . (int)$customer_type . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        np_city_id ='" . $this->db->escape($data['city_id']) . "',
        np_city_name ='" . $this->db->escape($data['city_name']) . "',
        np_street_id ='" . $this->db->escape($data['street_id']) . "',
        np_street_name ='" . $this->db->escape($data['street_name']) . "',
        np_house ='" . $this->db->escape($data['house']) . "',
        np_level ='" . $this->db->escape($data['level']) . "',
        np_apartment ='" . $this->db->escape($data['apartment']) . "',
        np_customer_name ='" . $this->db->escape($data['customer_name']) . "',
        np_customer_lastname ='" . $this->db->escape($data['customer_lastname']) . "', 
        np_customer_phone ='" . $this->db->escape($data['customer_phone']) . "' 
        WHERE `address_id` = '" . (int)$address_db['address_id'] . "'");
        break;
    }
  }


  public function importPriceGroupsClient()
  {
    $setting_module = $this->getSetting("module_ocimport");

    //отримуємо кліентів
    $customers = $this->getCustomers();
    if (!empty($customers)) {
      foreach ($customers as $customer) {

        $this->deleteCustomerAddressPriceGroupsByCustomer($customer['customer_id']);

        //отримуємо всі адреса
        $customer_addresses = $this->getAddresses($customer['customer_id']);
        if (!empty($customer_addresses)) {
          foreach ($customer_addresses as $address) {
            if (!empty($address['customer_cod_guid'])) {
              $curl = curl_init();

              $login = $setting_module['login'];
              $password = $setting_module['password'];
              // Формируем строку "логин:пароль" и кодируем в base64
              $authString = base64_encode("$login:$password");

              curl_setopt_array($curl, array(
                CURLOPT_URL => $setting_module['url'] . '/client-disconts/' . $address['customer_cod_guid'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                  'Authorization: Basic ' . $authString
                ),
              ));

              $response = curl_exec($curl);

              curl_close($curl);

              if (!empty($response)) {
                file_put_contents($this->STATUS_FILE, "load" . "\n");
                $this->savePriceGroupsClient($response, $customer['customer_id'], $address['address_id']);
              }
            }
          }
        }
      }
    }
  }

  public function savePriceGroupsClient($response, $customer_id, $address_id)
  {
    $response_mass = json_decode($response, true);

    if (!empty($response_mass['price_groups'])) {
      foreach ($response_mass['price_groups'] as $item) {

        $price_group_db = $this->getPriceGroupByUid($item['price_group_guid']);
        if (!empty($price_group_db)) {
          $price_group_id = $price_group_db['price_group_id'];
        } else {
          $price_group_id = $this->addPriceGroupe($item['price_group_guid'], $item['name']);
        }

        $progress = "Група : (ID: {$item['guid']})\n";
        file_put_contents($this->STATUS_FILE, $progress, FILE_APPEND | LOCK_EX);

        if ((int)$item['percent'] > 0) {
          $this->addCustomerAddressPriceGroup($item, $customer_id, $address_id, $price_group_id);
        }

      }
      file_put_contents($this->STATUS_FILE, "completed");
    } else {
      file_put_contents($this->STATUS_FILE, "error" . "\n");
      file_put_contents($this->STATUS_FILE, "Помилка: Не отримані дані для завантаження" . "\n", FILE_APPEND | LOCK_EX);
    }
  }

  private function deleteDiscontProductsByCustomerGroup($product_id, $customer_group_id)
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_discount` WHERE product_id = '" . (int)$product_id . "' AND customer_group_id = '" . (int)$customer_group_id . "'");
  }

  private function deleteCustomerAddressPriceGroupsByCustomer($customer_id)
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_address_price_groups` WHERE customer_id = '" . (int)$customer_id . "'");
  }

  private function getCustomerAddressPriceGroupsByUid($price_group_id, $customer_id, $address_id)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "customer_address_price_groups WHERE price_group_id = '" . (int)$price_group_id . "' AND customer_id = '" . (int)$customer_id . "' AND address_id = '" . (int)$address_id . "'");
    return $query->row;
  }

  private function updateCustomerAddressPriceGroup($item_db, $item, $customer_id, $address_id, $price_group_db)
  {
    $current_date = date('Y-m-d H:i:s');
    $this->query("UPDATE `" . DB_PREFIX . "customer_group_description` SET 
    percent = '" . (int)$item['percent'] . "',
    date_modified = '" . $current_date . "' 
    WHERE customer_address_price_group_id = '" . (int)$item_db['customer_address_price_group_id'] . "'");

  }

  private function addCustomerAddressPriceGroup($item, $customer_id, $address_id, $price_group_id)
  {
    $current_date = date('Y-m-d H:i:s');
    $this->query("INSERT INTO `" . DB_PREFIX . "customer_address_price_groups` SET 
    guid = '" . $this->db->escape($item['guid']) . "', 
    address_id = '" . (int)$address_id . "', 
    customer_id = '" . (int)$customer_id . "', 
    price_group_id = '" . (int)$price_group_id . "', 
    `percent` = '" . (int)$item['percent'] . "', 
    `status` = '1', 
    date_added = '" . $current_date . "', 
    date_modified = '" . $current_date . "'");
    return $this->db->getLastId();
  }


  public function getCustomers()
  {
    $sql = "SELECT *, CONCAT(c.firstname, ' ', c.lastname) AS name, cgd.name AS customer_group FROM " . DB_PREFIX . "customer c LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (c.customer_group_id = cgd.customer_group_id)";
    $query = $this->db->query($sql);
    return $query->rows;
  }

  public function importGroupe()
  {
    $setting_module = $this->getSetting("module_ocimport");

    $curl = curl_init();

    $login = $setting_module['login'];
    $password = $setting_module['password'];
    // Формируем строку "логин:пароль" и кодируем в base64
    $authString = base64_encode("$login:$password");

    curl_setopt_array($curl, array(
      CURLOPT_URL => $setting_module['url'] . '/discounts/type',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Basic ' . $authString
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    if (!empty($response)) {
      file_put_contents($this->STATUS_FILE, "load" . "\n");
      $this->saveGroupe($response);
    }
  }

  private function saveGroupe($response)
  {
    $groups = json_decode($response, true);
    if (!empty($groups['disconts_type'])) {
      foreach ($groups['disconts_type'] as $group) {

        $progress = "Група : (ID: {$group['guid']})\n";
        file_put_contents($this->STATUS_FILE, $progress, FILE_APPEND | LOCK_EX);

        $group_db = $this->getGroupByUid($group['guid']);
        if (!empty($group_db)) {
          $this->updateGroup($group_db, $group);
        } else {
          $this->addGroup($group);
        }
      }
      file_put_contents($this->STATUS_FILE, "completed");
    } else {
      file_put_contents($this->STATUS_FILE, "error" . "\n");
      file_put_contents($this->STATUS_FILE, "Помилка: Не отримані дані для завантаження" . "\n", FILE_APPEND | LOCK_EX);
    }
  }

  private function saveCards($response)
  {
    $cards = json_decode($response, true);
    if (!empty($cards['cards'])) {
      foreach ($cards['cards'] as $cart) {

        $progress = "Карта : (ID: {$cart['guid']})\n";
        file_put_contents($this->STATUS_FILE, $progress, FILE_APPEND | LOCK_EX);

        $card_db = $this->getCardByUid($cart['guid']);
        if ($card_db) {
          $this->updateCard($card_db, $cart);
        } else {
          $this->addCard($cart);
        }
      }
      file_put_contents($this->STATUS_FILE, "completed");
    } else {
      file_put_contents($this->STATUS_FILE, "error" . "\n");
      file_put_contents($this->STATUS_FILE, "Помилка: Не отримані дані для завантаження" . "\n", FILE_APPEND | LOCK_EX);
    }
  }

  private function addCard($data)
  {
    $sum = 0;
    if (isset($data['sum'])) {
      $sum = (int)$data['sum'];
    }
    $current_date = date('Y-m-d H:i:s');
    $this->query("INSERT INTO `" . DB_PREFIX . "cards` SET guid = '" . $this->db->escape($data['guid']) . "', card_code = '" . $this->db->escape($data['code']) . "', phone = '" . $this->db->escape($data['phone']) . "', card_name = '" . $this->db->escape($data['name']) . "', `sum` = '" . $sum . "', `card_code_qr` = '" . $this->db->escape($data["code_qr"]) . "', date_added = '" . $current_date . "', date_modified = '" . $current_date . "'");
    $card_id = $this->db->getLastId();
    return $card_id;
  }

  private function addGroup($data)
  {
    $this->query("INSERT INTO `" . DB_PREFIX . "customer_group` SET guid = '" . $this->db->escape($data['guid']) . "'");
    $group_id = $this->db->getLastId();

    $this->query("INSERT INTO `" . DB_PREFIX . "customer_group_description` SET customer_group_id = '" . (int)$group_id . "', name = '" . $this->db->escape($data['name']) . "' , language_id = '" . (int)$this->LANG_ID . "'");

    return $group_id;
  }

  private function updateGroup($group, $data = array())
  {
    $this->query("UPDATE `" . DB_PREFIX . "customer_group_description` SET name = '" . $this->db->escape($data['name']) . "' WHERE `customer_group_id` = '" . (int)$group["customer_group_id"] . "'");
  }

  private function updateCard(&$card, $data)
  {
    $sum = 0;
    if (isset($data['sum'])) {
      $sum = (int)$data['sum'];
    }
    $current_date = date('Y-m-d H:i:s');
    $this->query("UPDATE `" . DB_PREFIX . "cards` SET date_modified = '" . $current_date . "', `phone` = '" . $this->db->escape($data['phone']) . "', `card_code` = '" . $this->db->escape($data['code']) . "', `sum` = '" . $sum . "', card_name = '" . $this->db->escape($data['name']) . "', `card_code_qr` = '" . $this->db->escape($data["code_qr"]) . "' WHERE `card_id` = '" . (int)$card["card_id"] . "'");
    unset($card);
  }


  private function importWarehouses()
  {

    $setting_module = $this->getSetting("module_ocimport");

    $curl = curl_init();

    $login = $setting_module['login'];
    $password = $setting_module['password'];
    // Формируем строку "логин:пароль" и кодируем в base64
    $authString = base64_encode("$login:$password");

    curl_setopt_array($curl, array(
      CURLOPT_URL => $setting_module['url'] . '/stores',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Basic ' . $authString
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    if (!empty($response)) {
      $this->saveWarehouses($response);
    }
  }

  private function importCategories()
  {
    $curl = curl_init();

    $setting_module = $this->getSetting("module_ocimport");

    $login = $setting_module['login'];
    $password = $setting_module['password'];
    // Формируем строку "логин:пароль" и кодируем в base64
    $authString = base64_encode("$login:$password");

    curl_setopt_array($curl, array(
      CURLOPT_URL => $setting_module['url'] . '/categories',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Basic ' . $authString
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    if (!empty($response)) {
      $this->saveCategories($response);
    }
  }

  public function importProducts()
  {

    $setting_module = $this->getSetting("module_ocimport");

    // В начале скрипта
    $start_time = microtime(true);

    $login = $setting_module['login'];
    $password = $setting_module['password'];
    // Формируем строку "логин:пароль" и кодируем в base64
    $authString = base64_encode("$login:$password");

    if ($this->CRON > 0) {
      $this->STORE_COUNT = 50;
      $this->PRODUCT_COUNT = 1000000;
    } else {
      $this->STORE_COUNT = 50;
      $this->PRODUCT_COUNT = 100;
    }

    $warehousesIds = array();
    $warehouses = $this->getWarehouses();
    if (!empty($warehouses)) {
      $i = 0;
      foreach ($warehouses as $warehous) {

        $warehousesIds[] = $warehous['warehouse_id'];

        if ($i >= $this->STORE_COUNT) {
          break;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $setting_module['url'] . '/products/' . $warehous['uid'],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Basic ' . $authString
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        if (!empty($response)) {
          $this->saveProducts($warehous, $warehouses, $response, $i);
        }

        $i++;
      }


      $this->deleteProductQuantityByStore($warehousesIds);

    }


    file_put_contents($this->STATUS_FILE, "completed\n", LOCK_EX);

    $end_time = microtime(true);
    $execution_time = $end_time - $start_time;

    $all_time = "Час виконання : $execution_time" . "\n";
    file_put_contents($this->STATUS_FILE, $all_time, FILE_APPEND | LOCK_EX);
  }


  private function saveCategories($response)
  {
    //$this->clearCategories();
    $categories = json_decode($response, true);
    if (isset($categories['categories'])) {

      // Спочатку збираємо всі ID категорій з вхідного масиву
      $importedCategoryIds = array();

      foreach ($categories['categories'] as $cat) {
        $category = $this->getCategoryByUid($cat['id']);
        if ($category != null) {
          $importedCategoryIds[] = $category['category_id'];
          $this->updateCategory($category['category_id'], $cat);
        } else {
          $importedCategoryIds[] = $this->addCategory($cat);
        }
      }

      if (!empty($importedCategoryIds)) {
        $categories_all = $this->getCategories();
        foreach ($categories_all as $category_db) {
          $del_cat = 0;
          foreach ($importedCategoryIds as $category_id) {
            if ((int)$category_db['category_id'] == (int)$category_id) {
              $del_cat = 1;
              break;
            }
          }
          if ($del_cat <= 0) {
            $this->deleteCategory((int)$category_db['category_id']);
          }
        }
      }

    }
    if (isset($categories['categories_sub'])) {

      // Спочатку збираємо всі ID категорій з вхідного масиву
      $importedCategoryIds = array();

      foreach ($categories['categories_sub'] as $cat) {
        $category = $this->getCategoryByUid($cat['id']);
        if ($category != null) {
          $importedCategoryIds[] = $category['category_id'];
          $this->updateCategory($category['category_id'], $cat);
        } else {
          $importedCategoryIds[] = $this->addCategory($cat);
        }
      }

      if (!empty($importedCategoryIds)) {

        // Видаляємо категорії, яких немає в імпортованому списку
//        $placeholders = implode(',', array_fill(0, count($importedCategoryIds), '?'));
//        $sql = "DELETE FROM categories WHERE category_id NOT IN ($placeholders)";
//        $this->db->query($sql, $importedCategoryIds);

        $categories_all = $this->getSubCategories();
        foreach ($categories_all as $category_db) {
          $del_cat = 0;
          foreach ($importedCategoryIds as $category_id) {
            if ((int)$category_db['category_id'] == (int)$category_id) {
              $del_cat = 1;
              break;
            }
          }
          if ($del_cat <= 0) {
            $this->deleteCategory((int)$category_db['category_id']);
          }
        }
      }
    }
  }

  public function deleteCategory($category_id)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "'");

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_path WHERE path_id = '" . (int)$category_id . "'");

    foreach ($query->rows as $result) {
      $this->deleteCategory($result['category_id']);
    }

    $this->db->query("DELETE FROM " . DB_PREFIX . "category WHERE category_id = '" . (int)$category_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id = '" . (int)$category_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$category_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$category_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_related_wb WHERE category_id = '" . (int)$category_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "article_related_wb WHERE category_id = '" . (int)$category_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "coupon_category WHERE category_id = '" . (int)$category_id . "'");

    $this->deleteCache('category');

    if ($this->config->get('config_seo_pro')) {
      $this->deleteCache('seopro');
    }
  }

  private function saveWarehouses($response)
  {
    $warehouses = json_decode($response, true);
    if (!empty($warehouses)) {
      foreach ($warehouses['stores'] as $store) {
        $warehouse = $this->getWarehouseByUid($store['id']);
        if (!empty($warehouse)) {
          $this->updateWarehouse($warehouse, $store);
        } else {
          $this->addWarehouse($store);
        }
      }
    }
  }

  private function saveProducts($warehous, $warehouses, $response, $iterator)
  {
    $products = json_decode($response, true);
    $count_stores = count($warehouses);
    $warehouse_in_base = $this->getWarehouseByUid($warehous['uid']);
    $number_warehouse_load = $iterator + 1;

    //цінові групи
    if (!empty($products) && !empty($products['price_groups'])) {
      foreach ($products['price_groups'] as $groupe) {
        $group_db = $this->getPriceGroupByUid($groupe['guid']);
        if (!empty($group_db)) {
          $this->updatePriceGroupe($group_db, $groupe);
        } else {
          $this->addPriceGroupe($groupe['guid'], $groupe['name']);
        }
      }
    }

    //ціни
    if (!empty($products) && !empty($products['prices'])) {
      foreach ($products['prices'] as $price) {
        $price_db = $this->getPriceByUid($price['guid']);
        if (!empty($price_db)) {
          $this->updatePrice($price_db, $price);
        } else {
          $this->addPrice($price['guid'], $price['name']);
        }
      }
    }

    //накопичувальна програма
    if (!empty($products) && !empty($products['cumulative_discounts'])) {

      //чистимо знижки
      $this->db->query("TRUNCATE TABLE " . DB_PREFIX . "loyalty_discount;");

      $order_statuses = array_unique(array_merge((array)$this->config->get('config_processing_status'), (array)$this->config->get('config_complete_status')));
      $order_status_str = implode(',', $order_statuses);

      foreach ($products['cumulative_discounts'] as $groupe) {
        $status = 1;
        $customer_group_data = $this->getGroupByUid($groupe["price_groups_guid"]);

        if ($customer_group_data) {
          $customer_group_id = (int)$customer_group_data['customer_group_id'];
        } else {
          $customer_group_id = 1;
        }

        $sql = "INSERT INTO " . DB_PREFIX . "loyalty_discount SET ordertotal = '" . (int)$groupe["min_sum"] . "', customer_group_id = '" . (int)$customer_group_id . "', priority = '" . (int)$groupe["min_sum"] . "', order_status = '" . $this->db->escape($order_status_str) . "', percentage = '" . (float)$groupe["discont"] . "', status = '" . (int)$status . "'";
        $this->query($sql);
      }
    }

    //організації
    if (!empty($products) && !empty($products['organizations'])) {
      foreach ($products['organizations'] as $organization) {
        $organization_db = $this->getOrganizationByUid($organization['guid']);
        if (empty($organization_db)) {
          $this->addOrganization($organization['name'], $organization['guid']);
        }
      }
    }

    if (!empty($products) && !empty($products['products'])) {
      // 1. Пишемо перші дві строки одразу
      $header = "load_products\nМагазин: {$warehouse_in_base['name']} ({$warehous['uid']}) - {$number_warehouse_load} з {$count_stores}";
      file_put_contents($this->STATUS_FILE, $header . "\n", LOCK_EX);

      $i = 0;
      $count = count($products['products']);

      foreach ($products['products'] as $product) {

        //змінюємо "" на << >>
        $product['name'] = $this->replaceQuotesWithGuillemets($product['name']);

        // 2. Формуємо третю строку прогресу
        $progress = "Імпортовано товарів {$i}: з {$count}";

        // 3. Пишемо файл заново: дві старі строки + третя строка
        $new_content = $header . "\n" . $progress;
        file_put_contents($this->STATUS_FILE, $new_content, LOCK_EX);

        if ($i >= $this->PRODUCT_COUNT) {
          break;
        }

        $product_db = $this->getProductByUid($product['id']);
        if (!empty($product_db)) {
          $this->updateProduct((int)$warehous['warehouse_id'], $product_db, $product);
        } else {
          $this->addProduct((int)$warehous['warehouse_id'], $product);
        }
        $i++;
      }

      $this->deleteCache('product');

    }

    $imgfiles = glob(DIR_IMAGE . 'cache/*');

    if (!empty($imgfiles)) {
      foreach ($imgfiles as $imgfile) {
        $this->delDir($imgfile);
      }
    }

    $this->updateFilter();

    //тут треба записати час оновлення в налаштування
    date_default_timezone_set('Europe/Kiev');
    $current_date = date('Y-m-d H:i:s');
    $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($current_date) . "', serialized = '0' WHERE `code` = 'config' AND `key` = 'config_last_update' AND store_id = '" . $this->STORE_ID . "'");
  }

  public function getOrderStatuses($data = array()) {
    if ($data) {
      $sql = "SELECT * FROM " . DB_PREFIX . "order_status WHERE language_id = '" . (int)($this->config->get('config_language_id') ?: $this->getLanguageId($this->config->get('config_language'))) . "'";

      $sql .= " ORDER BY name";

      if (isset($data['order']) && ($data['order'] == 'DESC')) {
        $sql .= " DESC";
      } else {
        $sql .= " ASC";
      }

      if (isset($data['start']) || isset($data['limit'])) {
        if ($data['start'] < 0) {
          $data['start'] = 0;
        }

        if ($data['limit'] < 1) {
          $data['limit'] = 20;
        }

        $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
      }

      $query = $this->db->query($sql);

      return $query->rows;
    } else {
      $order_status_data = $this->cache->get('order_status.' . (int)($this->config->get('config_language_id') ?: $this->getLanguageId($this->config->get('config_language'))));

      if (!$order_status_data) {
        $query = $this->db->query("SELECT order_status_id, name FROM " . DB_PREFIX . "order_status WHERE language_id = '" . (int)($this->config->get('config_language_id') ?: $this->getLanguageId($this->config->get('config_language'))) . "' ORDER BY name");

        $order_status_data = $query->rows;

      }

      return $order_status_data;
    }
  }

  public function delDir($dirname)
  {
    if (file_exists($dirname)) {
      if (is_dir($dirname)) {
        $dir = opendir($dirname);
        while (($filename = readdir($dir)) !== false) {
          if ($filename != "." && $filename != "..") {
            $file = $dirname . "/" . $filename;
            $this->delDir($file);
          }
        }
        closedir($dir);
        rmdir($dirname);
      } else {
        @unlink($dirname);
      }
    }
  }

  public function deleteCache(string $key)
  {
    $files = glob(DIR_CACHE . 'cache.' . basename($key) . '.*');

    if ($files) {
      foreach ($files as $file) {
        if (!@unlink($file)) {
          clearstatcache(false, $file);
        }
      }
    }
  }

  private function updatePriceGroupe($group_db, $data)
  {
    $current_date = date('Y-m-d H:i:s');
    $this->query("UPDATE `" . DB_PREFIX . "price_groups` SET name = '" . $this->db->escape($data['name']) . "', `date_modified` = '" . $current_date . "' WHERE `price_group_id` = " . (int)$group_db['price_group_id']);
  }

  private function addPriceGroupe($guid, $name)
  {
    $current_date = date('Y-m-d H:i:s');
    $this->query("INSERT INTO `" . DB_PREFIX . "price_groups` SET uid = '" . $this->db->escape($guid) . "', name = '" . $this->db->escape($name) . "', status = 1, `date_added` = '" . $current_date . "', `date_modified` = '" . $current_date . "'");
    return $this->db->getLastId();
  }

  private function updatePrice($price_db, $data)
  {
    $this->query("UPDATE `" . DB_PREFIX . "customer_price` SET name = '" . $this->db->escape($data['name']) . "' WHERE `customer_price_id` = " . (int)$price_db['customer_price_id']);
  }

  private function addPrice($guid, $name)
  {
    $this->query("INSERT INTO `" . DB_PREFIX . "customer_price` SET guid = '" . $this->db->escape($guid) . "', name = '" . $this->db->escape($name) . "'");
    return $this->db->getLastId();
  }

  private function addDiscontProduct($product_id, $customer_group_id, $options, $options_value, $data)
  {
    $date_start = date('Y-m-d H:i:s');
    $date_end = date('Y-m-d H:i:s');
    $percent = $data[0]['percent'];
    $this->query("INSERT INTO `" . DB_PREFIX . "product_discount` SET `product_id` = '" . (int)$product_id . "', `customer_group_id` = '" . (int)$customer_group_id . "', `percentage` = '" . (int)$percent . "', `options` = '" . $this->db->escape($options) . "', `options_value` = '" . $this->db->escape($options_value) . "',  `date_start` = '" . $date_start . "', `date_end` = '" . $date_end . "'");
    return $this->db->getLastId();
  }


  private function addProduct($warehous_id, $data)
  {

    $data['status'] = 1;

    // Статус на складе
    $data['stock_status_id'] = 7;

    // ЕДИНИЦА ВЕСА
    if ($this->config->get('config_weight_class_id')) {
      $data['weight_class_id'] = $this->config->get('config_weight_class_id');
    }

    //BRAND
    if (isset($data['brand'])) {
      $data['manufacturer_id'] = $this->setBrand($data['brand']);
    }

    // Подготовим список полей по которым есть данные
    $current_date = date('Y-m-d H:i:s');
    $fields = $this->prepareQueryProduct($data);

    $price_groupe_id = 0;
    if (isset($data['price_groupe'])) {
      if (($data['price_groupe']) > 0) {
        $group_db = $this->getPriceGroupByUid($data['price_groupe']);
        if (!empty($group_db)) {
          $price_groupe_id = $group_db['price_group_id'];
        }
      }
    }

    //організація для відвантаження товару
    $organization_id = 0;
    if (isset($data['organization'])) {
      $organization_db = $this->getOrganizationByUid($data['organization']);
      if (!empty($organization_db)) {
        $organization_id = $organization_db['organization_id'];
      }
    }

    if ($fields) {
      if (isset($data['product_id'])) {
        $fields = "`product_id` = " . $data['product_id'] . (empty($fields) ? "" : ", " . $fields);
      }
      $this->query("INSERT INTO `" . DB_PREFIX . "product` SET " . $fields . ", price_group_id = '" . (int)$price_groupe_id . "', organization_id = '" . (int)$organization_id . "', `date_added` = '" . $current_date . "', `date_modified` = '" . $current_date . "'");
      $product_id = $this->db->getLastId();
    } else {
      return 0;
    }

    $query = "product_id=" . (int)$product_id;
    if (isset($data['name'])) {

      //якщо однакові назви товару то додаємо категорію
      $category_slug = '';
      if (isset($data['catId'])) {
        $cat_in_site = $this->getCategoryByUid($data['catId']);
        if (!empty($cat_in_site)) {
          $query_cat = "category_id=" . $cat_in_site['parent_id'];
          $slug = $this->getSlug((int)$this->STORE_ID, (int)$this->LANG_ID, $query_cat);
          if ($slug != null) {
            $category_slug = $slug['keyword'];
          }
        }
      }

      $keyword_new = $this->translit(trim($data['name']));

      $url = $this->uniqueSlug((int)$this->STORE_ID, (int)$this->LANG_ID, $keyword_new, $query, $category_slug);
      $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($url) . "', language_id = '" . (int)$this->LANG_ID . "', store_id = '" . $this->STORE_ID . "'");
    }

    if (isset($data['meta_title'])) {
      $data['meta_title'] = $data['meta_title'];
    } else {
      $data['meta_title'] = "";
    }

    $fields = $this->prepareQueryDescription($data, "set");
    $this->query("INSERT INTO `" . DB_PREFIX . "product_description` SET `product_id` = " . (int)$product_id . ", `language_id` = " . $this->LANG_ID . ", " . $fields);
    $this->log("Товар добавлен, product_id = " . $product_id, 2);

    // Пропишем товар в магазин
    $this->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET `product_id` = " . (int)$product_id . ", `store_id` = " . $this->STORE_ID);
    $this->log("Товар добавлен в магазин, store_id = " . $warehous_id, 2);

    //ATTRIBUTES
    if (isset($data['opts'])) {
      $this->setAttributes($product_id, $data['opts']);
    }

    //CALCULATOR
    if (isset($data['calculator'])) {
      $calculator_json = $this->db->escape(json_encode($data['calculator'], JSON_UNESCAPED_UNICODE));
      $this->query("UPDATE `" . DB_PREFIX . "product` SET `calc` = '" . $calculator_json . "' WHERE `product_id` = " . (int)$product_id);
    }

    $customerGroupUids = ["286", "546", "241", "0"];

    foreach ($customerGroupUids as $uid) {
      $customerGroup = $this->getGroupByUid($uid);
      if ($customerGroup != null) {
        $this->deleteDiscontProductsByCustomerGroup($product_id, $customerGroup['customer_group_id']);
      }
    }

    // Записываем опции
    foreach ($data['variants'] as $variant) {

      // ЕДИНИЦА
      $unit = $this->getUnitByName($variant['unit']);
      if (!empty($unit)) {
        $data['length_class_id'] = $unit['length_class_id'];
      } else {
        $data['length_class_id'] = $this->addUnitByName($variant['unit']);
      }

      //КАРТИНКА
      $this->addProductImage($product_id, trim($variant['barcode']));

      $price = 0;
      if (!empty($variant['price'])) {
        $price = (float)$variant['price'];
      }
      $price_base = 0;
      if (!empty($variant['price_base'])) {
        $price_base = (float)$variant['price_base'];
      }

      $weight = 0;
      if (!empty($variant['weight'])) {
        $weight = (float)$variant['weight'];
      }

      $quantity = 0;
      if (!empty($variant['quantity'])) {
        $quantity = (float)$variant['quantity'];
      }

      $prod_status_id = 0;
      if (isset($variant['top'])) {
        if ($variant['top'] > 0) {
          //Тут шукаэмо статус топ
          $prod_status = $this->getStatusProduct("Топ");
          if (!empty($prod_status)) {
            $prod_status_id = $prod_status['product_status_id'];
          } else {
            $prod_status_id = $this->addStatusProduct("Топ", "top");
          }
        }
      }

      $barcode = "";
      if (!empty($variant['barcode'])) {
        $barcode = trim($variant['barcode']);
      }

      $options = array();
      $opts = array();
      $opts_value = array();

      if (isset($variant['var'])) {
        $options = $this->setProductOptions($product_id, $variant, $barcode, $weight, $quantity, $price, $price_base, $prod_status_id);
      }

      if (!empty($options)) {
        foreach ($options as $option_id => $value) {
          $opts[(int)$option_id] = (int)$value['option_value_id'];
          $opts_value[(int)$value['product_option_id']] = (int)$value['product_option_value_id'];
        }
      }

      $opts_json = json_encode($opts);
      $opts_value_json = json_encode($opts_value);

      $this->db->query("INSERT INTO " . DB_PREFIX . "product_quantity SET product_id = '" . (int)$product_id . "', warehouse_id = '" . (int)$warehous_id . "', price = '" . (float)$price . "', price_base = '" . (float)$price_base . "', quantity = '" . (float)$quantity . "', ean = '" . $barcode . "', sku = '" . $variant['sku'] . "', guid = '" . $variant['guid'] . "', `options` = '" . $this->db->escape($opts_json) . "', `options_value` = '" . $this->db->escape($opts_value_json) . "'");

      //ЗНИЖКА ДЛЯ ИНТЕРНЕТ МАГАЗИНУ
      if (isset($variant['discont'])) {
        if (!empty($variant['discont'])) {
          foreach ($customerGroupUids as $uid) {
            $customerGroup = $this->getGroupByUid($uid);
            if ($customerGroup != null) {
              $this->addDiscontProduct($product_id, $customerGroup['customer_group_id'], $opts_json, $opts_value_json, $variant['discont']);
            }
          }
        }
      }
    }

    // КАТЕГОРИИ

    $this->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . $product_id . "'");

    // Категории альтернативні
    if (isset($data['catSubId'])) {
      if (count($data['catSubId']) > 0) {
        for ($i = 0; $i < count($data['catSubId']); $i++) {
          $category_sub = $this->getCategoryByUid($data['catSubId'][$i]);
          if (!empty($category_sub)) {
            $this->addProductCategories($product_id, $category_sub['category_id']);
          }
        }
      }
    }

    //Категорії товарів
    if (isset($data['catId'])) {
      $category = $this->getCategoryByUid($data['catId']);
      if (!empty($category)) {
        $this->addProductCategories($product_id, $category['category_id']);
      }
    }


    return $product_id;
  }

  private function updateProduct($warehous_id, $old_data, $data)
  {

    $data['status'] = 1;

    // Статус на складе
    $data['stock_status_id'] = 7;

    //BRAND
    if (isset($data['brand'])) {
      $data['manufacturer_id'] = $this->setBrand($data['brand']);
    }

    // Для SEO объеденим старые и новые данные для полной картины
    $modify_fields1 = $this->compareArraysData($data, $old_data);

    // Формируем SEO для товара и получаем поля которые изменились
    $modify_fields2 = array();
    $modify_fields = array_merge($modify_fields1, $modify_fields2);

    // Формируем поля для обновления таблицы product
    $update_fields = $this->prepareQueryProduct($modify_fields, 'set');

    $price_groupe_id = 0;
    if (isset($data['price_groupe'])) {
      if (($data['price_groupe']) > 0) {
        $group_db = $this->getPriceGroupByUid($data['price_groupe']);
        if (!empty($group_db)) {
          $price_groupe_id = $group_db['price_group_id'];
        }
      }
    }

    //організація для відвантаження товару
    $organization_id = 0;
    if (isset($data['organization'])) {
      $organization_db = $this->getOrganizationByUid($data['organization']);
      if (!empty($organization_db)) {
        $organization_id = $organization_db['organization_id'];
      }
    }

    $current_date = date('Y-m-d H:i:s');
    if ($update_fields) {
      $this->query("UPDATE `" . DB_PREFIX . "product` SET " . $update_fields . ", price_group_id = '" . (int)$price_groupe_id . "', organization_id = '" . (int)$organization_id . "', `date_modified` = '" . $current_date . "' WHERE `product_id` = " . (int)$old_data['product_id']);
    } elseif ($modify_fields) {
      // Если было хоть одно изменение, пропишем дату обновления товара
      $this->query("UPDATE `" . DB_PREFIX . "product` SET price_group_id = '" . (int)$price_groupe_id . "', organization_id = '" . (int)$organization_id . "', `date_modified` = '" . $current_date . "' WHERE `product_id` = " . (int)$old_data['product_id']);
    } else {
      $this->query("UPDATE `" . DB_PREFIX . "product` SET price_group_id = '" . (int)$price_groupe_id . "', organization_id = '" . (int)$organization_id . "', `date_modified` = '" . $current_date . "' WHERE `product_id` = " . (int)$old_data['product_id']);
    }

    // Обновляем описание
    $update_fields = $this->prepareQueryDescription($modify_fields, 'set');
    if ($update_fields) {
      $this->query("UPDATE `" . DB_PREFIX . "product_description` SET " . $update_fields . " WHERE `product_id` = " . $old_data['product_id']);
    }

    // Освободим память
    unset($update_fields);
    unset($organization_id);
    unset($price_groupe_id);

    //ATTRIBUTES
    if (isset($data['opts'])) {
      $this->setAttributes($old_data['product_id'], $data['opts']);
    }

    //CALCULATOR
    if (isset($data['calculator'])) {
      $calculator_json = $this->db->escape(json_encode($data['calculator'], JSON_UNESCAPED_UNICODE));
      $this->query("UPDATE `" . DB_PREFIX . "product` SET `calc` = '" . $calculator_json . "' WHERE `product_id` = " . (int)$old_data['product_id']);
    }

    // Получаем все текущие опции
    $current_options = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_quantity` WHERE product_id = '" . (int)$old_data['product_id'] . "' AND warehouse_id = '" . (int)$warehous_id . "'");

    // Массив активных комбинаций опций
    $active_combinations = array();

    //Видаляємо всі знижки у товару
    $customerGroupUids = ["286", "546", "241", "0"];
    foreach ($customerGroupUids as $uid) {
      $customerGroup = $this->getGroupByUid($uid);
      if ($customerGroup != null) {
        $this->deleteDiscontProductsByCustomerGroup($old_data['product_id'], $customerGroup['customer_group_id']);
      }
    }

    // Записываем опции
    foreach ($data['variants'] as $variant) {

      // ЕДИНИЦА
      $unit = $this->getUnitByName($variant['unit']);
      if (!empty($unit)) {
        $data['length_class_id'] = $unit['length_class_id'];
      } else {
        $data['length_class_id'] = $this->addUnitByName($variant['unit']);
      }

      //КАРТИНКА
      $this->addProductImage($old_data['product_id'], trim($variant['barcode']));

      $price = 0;
      if (!empty($variant['price'])) {
        $price = (float)$variant['price'];
      }

      $price_base = 0;
      if (!empty($variant['price_base'])) {
        $price_base = (float)$variant['price_base'];
      }

      $weight = 0;
      if (!empty($variant['weight'])) {
        $weight = (float)$variant['weight'];
      }

      $quantity = 0;
      if (!empty($variant['quantity'])) {
        $quantity = (float)$variant['quantity'];
      }

      $prod_status_id = 0;
      if (isset($variant['top'])) {
        if ($variant['top'] > 0) {
          //Тут шукаэмо статус топ
          $prod_status = $this->getStatusProduct("Топ");
          if (!empty($prod_status)) {
            $prod_status_id = $prod_status['product_status_id'];
          } else {
            $prod_status_id = $this->addStatusProduct("Топ", "top");
          }
        }
      }

      $barcode = "";
      if (!empty($variant['barcode'])) {
        $barcode = trim($variant['barcode']);
      }

      $options = array();
      $opts = array();
      $opts_value = array();

      if (isset($variant['var'])) {
        $options = $this->setProductOptions($old_data['product_id'], $variant, $barcode, $weight, $quantity, $price, $price_base, $prod_status_id);
      }

      if (!empty($options)) {
        foreach ($options as $option_id => $value) {
          $opts[(int)$option_id] = (int)$value['option_value_id'];
          $opts_value[(int)$value['product_option_id']] = (int)$value['product_option_value_id'];
        }
      }

      ksort($opts);
      ksort($opts_value);

      $opts_json = json_encode($opts);
      $opts_value_json = json_encode($opts_value);

      // Спочатку перевіряємо чи існує запис
      $found_row = null;
      foreach ($current_options->rows as $row) {
        if ($barcode != '' && $row['ean'] == $barcode) {
          $found_row = $row;
          break;
        }

        // Порівнюємо опції як масиви
        $row_opts = json_decode($row['options'], true);
        $row_opts_value = json_decode($row['options_value'], true);

        if ($row_opts == $opts && $row_opts_value == $opts_value) {
          $found_row = $row;
          break;
        }
      }

      if ($found_row) {
        // Якщо запис існує - оновлюємо
        $this->db->query("UPDATE `" . DB_PREFIX . "product_quantity` 
        SET guid = '" . $this->db->escape($variant['guid']) . "', 
            price = '" . (float)$price . "',
            price_base = '" . (float)$price_base . "', 
            quantity = '" . (float)$quantity . "', 
            sku = '" . $this->db->escape($variant['sku']) . "',
            options = '" . $this->db->escape($opts_json) . "',
            options_value = '" . $this->db->escape($opts_value_json) . "'
        WHERE product_id = '" . (int)$old_data['product_id'] . "' 
        AND warehouse_id = '" . (int)$warehous_id . "' 
        AND options = '" . $this->db->escape($found_row['options']) . "' 
        AND options_value = '" . $this->db->escape($found_row['options_value']) . "' 
        AND ean = '" . $this->db->escape($found_row['ean']) . "'");
      } else {
        // Якщо запису немає - вставляємо новий
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_quantity` 
        (product_id, warehouse_id, options, options_value, price, price_base, quantity, ean, sku, guid) 
        VALUES (
            '" . (int)$old_data['product_id'] . "', 
            '" . (int)$warehous_id . "', 
            '" . $this->db->escape($opts_json) . "', 
            '" . $this->db->escape($opts_value_json) . "', 
            '" . (float)$price . "',
            '" . (float)$price_base . "', 
            '" . (float)$quantity . "', 
            '" . $this->db->escape($barcode) . "', 
            '" . $this->db->escape($variant['sku']) . "', 
            '" . $this->db->escape($variant['guid']) . "'
        )");
      }

      $active_combinations[] = [
        'options' => $opts_json,
        'options_value' => $opts_value_json
      ];

      //ЗНИЖКА ДЛЯ ИНТЕРНЕТ МАГАЗИНУ
      if (isset($variant['discont'])) {
        if (!empty($variant['discont'])) {
          foreach ($customerGroupUids as $uid) {
            $customerGroup = $this->getGroupByUid($uid);
            if ($customerGroup != null) {
              $this->addDiscontProduct($old_data['product_id'], $customerGroup['customer_group_id'], $opts_json, $opts_value_json, $variant['discont']);
            }
          }
        }
      }

    }

    // Удаляем неиспользуемые строки
    foreach ($current_options->rows as $row) {
      $is_active = false;

      $row_opts = json_decode($row['options'], true);
      $row_opts_value = json_decode($row['options_value'], true);

      // Проверяем, является ли комбинация активной
      foreach ($active_combinations as $active) {
        $active_opts = json_decode($active['options'], true);
        $active_opts_value = json_decode($active['options_value'], true);

        if ($row_opts == $active_opts && $row_opts_value == $active_opts_value) {
          $is_active = true;
          break;
        }
      }

      // Если комбинация больше не используется, удаляем ее
      if (!$is_active) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_quantity` WHERE product_id = '" . (int)$old_data['product_id'] . "' AND warehouse_id = '" . (int)$warehous_id . "' AND options = '" . $this->db->escape($row['options']) . "' AND options_value = '" . $this->db->escape($row['options_value']) . "'");
      }
    }


    // КАТЕГОРИИ

    $this->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . $old_data['product_id'] . "'");

    // Категории альтернативні
    if (isset($data['catSubId'])) {
      if (count($data['catSubId']) > 0) {
        for ($i = 0; $i < count($data['catSubId']); $i++) {
          $category_sub = $this->getCategoryByUid($data['catSubId'][$i]);
          if (!empty($category_sub)) {
            $this->addProductCategories($old_data['product_id'], $category_sub['category_id']);
          }
        }
      }
    }

    //Категорії товарів
    if (isset($data['catId'])) {
      $category = $this->getCategoryByUid($data['catId']);
      if (!empty($category)) {
        $this->addProductCategories($old_data['product_id'], $category['category_id']);
      }
    }


  }

  private function setBrand($data)
  {
    $manufactuter_id = 0;
    if (!empty($data)) {
      $manufactuter = $this->getManufacturerUid($data[0]['guid']);
      if (!empty($manufactuter)) {
        $manufactuter_id = $manufactuter['manufacturer_id'];
        $this->updateManufacturer($manufactuter['manufacturer_id'], $data[0]);
      } else {
        $manufactuter_id = $this->addManufacturer($data[0]);
      }
    }
    return $manufactuter_id;
  }

  private function setAttributes($product_id, $attibutess)
  {
    $product_attributes = array();


    foreach ($attibutess as $value) {

      if (!empty($value['name'])) {
        // Получим старые данные
        $attrib = $this->getAttributeByUid($value['guid']);
        if (!empty($attrib)) {
          // Проверим изменения
          $attrib_id = (int)$attrib['attribute_id'];

          if (empty($attrib['slug'])) {
            $slug = $this->translit($value['name']);
          } else {
            $slug = $attrib['slug'];
          }
          $this->query("UPDATE `" . DB_PREFIX . "attribute_description` SET `name` = '" . $this->db->escape($value['name']) . "', slug = '" . $slug . "' WHERE `attribute_id` = '" . (int)$attrib['attribute_id'] . "' AND language_id = '" . (int)$this->LANG_ID . "'");
        } else {
          // Добавить значение
          $this->query("INSERT INTO `" . DB_PREFIX . "attribute` SET  `guid` = '" . $this->db->escape($value['guid']) . "', `attribute_group_id` = 8");
          $attrib_id = $this->db->getLastId();

          $slug = $this->translit($value['name']);
          $this->query("INSERT INTO `" . DB_PREFIX . "attribute_description` SET `name` = '" . $this->db->escape($value['name']) . "', `slug` = '" . $slug . "', `attribute_id` = '" . (int)$attrib_id . "', language_id = '" . (int)$this->LANG_ID . "'");
        }
        $product_attributes[] = array(
          'name' => $value['name'],
          'value' => $value['value'],
          'guid' => $value['guid'],
          'attribute_id' => $attrib_id
        );
      }
    }

    $this->updateProductAttributes($product_id, $product_attributes);
  }

  private function updateProductAttributes($product_id, $attributes)
  {

    // Читаем старые атрибуты
    $product_attributes = array();
    $query = $this->query("SELECT `attribute_id`,`text` FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = " . (int)$product_id . " AND `language_id` = " . $this->LANG_ID);
    if (!empty($query->rows)) {
      foreach ($query->rows as $attribute) {
        $product_attributes[$attribute['attribute_id']] = $attribute['text'];
      }
    }

    foreach ($attributes as $attribute) {
      if (isset($attribute['attribute_id'])) {
        if (isset($product_attributes[$attribute['attribute_id']])) {
          if ($product_attributes[$attribute['attribute_id']] != $attribute['value']) {
            $this->query("UPDATE `" . DB_PREFIX . "product_attribute` SET `text` = '" . $this->db->escape($attribute['value']) . "' WHERE `product_id` = " . (int)$product_id . " AND `attribute_id` = " . (int)$attribute['attribute_id'] . " AND `language_id` = " . $this->LANG_ID);
          }
          // Удаляем из массива атрибутов, так как он был обработан
          unset($product_attributes[$attribute['attribute_id']]);
        } else {
          $this->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SET `product_id` = " . (int)$product_id . ", `attribute_id` = " . (int)$attribute['attribute_id'] . ", `language_id` = " . $this->LANG_ID . ", `text` = '" . $this->db->escape($attribute['value']) . "'");
        }
      }
    }

    // Удалим неиспользованные
    if (count($product_attributes)) {
      $delete_attribute = array();
      foreach ($product_attributes as $attribute_id => $attribute) {
        $delete_attribute[] = $attribute_id;
      }
      $this->query("DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = " . (int)$product_id . " AND `language_id` = " . $this->LANG_ID . " AND `attribute_id` IN (" . implode(",", $delete_attribute) . ")");
    }
  }

  private function setProductOptions($product_id, $variant, $barcode, $weight, $quantity, $price, $price_base, $top)
  {

    //СВОЙСТВО
    $product_options = $this->getProductOptions($product_id);

    $data_options = array();
    foreach ($variant['var'] as $item) {
      if (!empty(trim($item['value']))) {
        $data_options[$item['name']] = array(
          'guid' => (string)$item['id'],
          'value' => strtolower($item['value']),
          'description' => $item['description'],
          'composition' => $item['composition'],
        );
      }
    }

    $options = array();

    foreach ($data_options as $name => $option) {

      $type = "radio";

      // Опция в спавочнике
      if (!empty($name)) {
        $option_id = $this->setOption($name, $type, $option['guid']);

        // Значение опции в справочнике
        $option_value_id = $this->setOptionValue(strtolower($option['value']), $option_id, "", "");

        // Найдем опцию по имени
        $product_option_id = $this->setProductOption($product_id, $option_id, $product_options);

        $data_value = array(
          'name' => strtolower($option['value']),
          'quantity' => $quantity,
          'subtract' => 1,
          'price' => $price,
          'price_base' => $price_base,
          'product_status_id' => $top,
          'weight' => $weight
        );

        $product_option_value_id = $this->setProductOptionValue($product_id, $product_option_id, $option_id, $option_value_id, $data_value, $product_options);

        //option value desc
        $product_option_value_desc = $this->getProductOptionValueDescription($product_option_value_id);
        if ($product_option_value_desc != null) {
          if (!empty($option['description'])) {
            $this->updateProductOptionValueDescription($product_option_value_id, $option['description']);
          } else {
            $this->deleteProductOptionValueDescription($product_option_value_id);
          }
        } else {
          if (!empty($option['description'])) {
            $this->addProductOptionValueDescription($product_option_value_id, $option['description']);
          }
        }

        //option value composition
        $product_option_value_composition = $this->getProductOptionValueComposition($product_option_value_id);
        if ($product_option_value_composition != null) {
          if (!empty($option['composition'])) {
            $this->updateProductOptionValueComposition($product_option_value_id, $option['composition']);
          } else {
            $this->deleteProductOptionValueComposition($product_option_value_id);
          }
        } else {
          if (!empty($option['composition'])) {
            $this->addProductOptionValueComposition($product_option_value_id, $option['composition']);
          }
        }

        //option value image
        if (!empty($barcode)) {
          $this->addOptionValueImage($product_id, $product_option_value_id, $barcode);
        }

        $options[$option_id] = array(
          "product_option_id" => $product_option_id,
          "product_option_value_id" => $product_option_value_id,
          "option_value_id" => $option_value_id
        );
      }
    }

    return $options;
  }

  private function getProductOptionValueDescription($product_option_value_id)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product_option_value_description WHERE product_option_value_id = '" . (int)$product_option_value_id . "' AND language_id = '" . (int)$this->LANG_ID . "'");
    return $query->row;
  }

  private function addProductOptionValueDescription($product_option_value_id, $text)
  {
    $this->query("INSERT INTO `" . DB_PREFIX . "product_option_value_description` SET `text` = '" . $this->db->escape($text) . "', product_option_value_id = '" . (int)$product_option_value_id . "', language_id = '" . (int)$this->LANG_ID . "'");
    return $this->db->getLastId();
  }

  private function updateProductOptionValueDescription($product_option_value_id, $text)
  {
    $this->query("UPDATE `" . DB_PREFIX . "product_option_value_description` SET `text` = '" . $this->db->escape($text) . "' WHERE product_option_value_id = '" . (int)$product_option_value_id . "' AND language_id = '" . (int)$this->LANG_ID . "'");
    return $this->db->getLastId();
  }

  private function deleteProductOptionValueDescription($product_option_value_id)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value_description WHERE product_option_value_id = '" . (int)$product_option_value_id . "' AND language_id = '" . (int)$this->LANG_ID . "'");
  }


  private function getProductOptionValueComposition($product_option_value_id)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product_option_value_composition WHERE product_option_value_id = '" . (int)$product_option_value_id . "' AND language_id = '" . (int)$this->LANG_ID . "'");
    return $query->row;
  }

  private function addProductOptionValueComposition($product_option_value_id, $text)
  {
    $this->query("INSERT INTO `" . DB_PREFIX . "product_option_value_composition` SET `text` = '" . $this->db->escape($text) . "', product_option_value_id = '" . (int)$product_option_value_id . "', language_id = '" . (int)$this->LANG_ID . "'");
    return $this->db->getLastId();
  }

  private function updateProductOptionValueComposition($product_option_value_id, $text)
  {
    $this->query("UPDATE `" . DB_PREFIX . "product_option_value_composition` SET `text` = '" . $this->db->escape($text) . "' WHERE product_option_value_id = '" . (int)$product_option_value_id . "' AND language_id = '" . (int)$this->LANG_ID . "'");
    return $this->db->getLastId();
  }

  private function deleteProductOptionValueComposition($product_option_value_id)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value_composition WHERE product_option_value_id = '" . (int)$product_option_value_id . "' AND language_id = '" . (int)$this->LANG_ID . "'");
  }


  private function setProductOptionValue($product_id, $product_option_id, $option_id, $option_value_id, $data_value, $product_options = array())
  {

    if (isset($product_options[$product_option_id]['values'])) {
      foreach ($product_options[$product_option_id]['values'] as $product_option_value_id => $product_option_value) {
        if ($product_option_value['option_value_id'] == $option_value_id) {

          $update_fields = $this->compareArraysData($data_value, $product_option_value);
          if ($update_fields) {
            $sql_set = $this->prepareQuery($update_fields, 'set', 'product_option_value');
            if ($sql_set) {
              $this->query("UPDATE `" . DB_PREFIX . "product_option_value` SET " . $sql_set . " WHERE `product_option_value_id` = " . (int)$product_option_value_id);
            }
          }
          return $product_option_value_id;
        }
      }
    }

    // Нету
    if (empty($data_value['sort_order'])) $data_value['sort_order'] = 0;
    if (empty($data_value['price'])) $data_value['price'] = 0;
    if (empty($data_value['price_base'])) $data_value['price_base'] = 0;
    if (empty($data_value['top'])) $data_value['top'] = 0;
    if (empty($data_value['weight'])) $data_value['weight'] = 0.0;
    if (!isset($data_value['subtract'])) $data_value['subtract'] = 1;
    $this->query("INSERT INTO `" . DB_PREFIX . "product_option_value` SET `product_option_id` = " . (int)$product_option_id . ", `product_id` = " . (int)$product_id . ", `option_id` = " . (int)$option_id . ", `option_value_id` = " . (int)$option_value_id . ",`weight` = " . $data_value['weight'] . ", `subtract` = " . (int)$data_value['subtract']);
    return $this->db->getLastId();

  }

  private function setProductOption($product_id, $option_id, $product_options, $required = 1)
  {

    $this->log("Обработка опции для товара product_id = " . $product_id, 2);
    //$this->log($product_options, 2);

    foreach ($product_options as $product_option) {
      if ($product_option['option_id'] == $option_id) {
        $this->log("Найдена опция у товара product_option_id = " . $product_option['product_option_id'] . " по option_id = " . $option_id);
        return $product_option['product_option_id'];
      }
    }

    // Нету
    $this->query("INSERT INTO `" . DB_PREFIX . "product_option` SET `product_id` = " . (int)$product_id . ", `option_id` = " . (int)$option_id . ", `required` = " . $required);
    $product_option_id = $this->db->getLastId();
    return $product_option_id;
  }

  private function getProductByUid($uid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.jan = '" . $uid . "'");
    return $query->row;
  }

  private function getPriceGroupByUid($uid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "price_groups WHERE uid = '" . $uid . "'");
    return $query->row;
  }

  private function getPriceByUid($guid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "customer_price WHERE guid = '" . $guid . "'");
    return $query->row;
  }

  private function getProduct($product_id)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd2 ON (pd2.product_id = p.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd2.language_id = '" . (int)$this->LANG_ID . "'");
    return $query->row;
  }

  private function getProductQtyByUid($guid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product_quantity WHERE guid = '" . $guid . "'");
    return $query->row;
  }

  private function getOptByUid($uid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "option WHERE guid = '" . $uid . "'");
    return $query->row;
  }

  private function getAttributeByUid($uid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "attribute a LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (ad.attribute_id = a.attribute_id) WHERE a.guid = '" . $this->db->escape($uid) . "' AND ad.language_id = '" . (int)$this->LANG_ID . "'");
    return $query->row;
  }

  private function getManufacturerUid($uid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "manufacturer WHERE guid = '" . $this->db->escape($uid) . "'");
    return $query->row;
  }

  private function addOptionValueImage($product_id, $product_option_value_id, $ean)
  {
    if (empty(trim($ean))) return;

    $dir = DIR_IMAGE . "/catalog/product";
    if (!is_dir($dir)) return;

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
      $name_file = explode(".", $file)[0];
      $is_additional = (count(explode("_", $name_file)) > 1);

      if (!$is_additional) {
        if ($ean == $name_file) {
          $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET image_opt = '" . $this->db->escape("catalog/product/" . $file) . "' WHERE product_option_value_id = '" . $product_option_value_id . "' AND product_id = '" . $product_id . "'");
        }
      }
    }
    // Очищаємо таблицю додаткових зображень опцій, бо вони тепер йдуть в загальну таблицю product_image
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value_image WHERE product_option_value_id = '" . (int)$product_option_value_id . "'");
  }

  private function addProductImage($prod_id, $ean)
  {
    if (empty(trim($ean))) return;

    // Перевіряємо чи є у товара опції
    $has_options = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$prod_id . "'")->num_rows > 0;

    $dir = DIR_IMAGE . "/catalog/product";
    if (!is_dir($dir)) return;

    $massFindProds = array();
    // Використовуємо glob замість scandir для значного прискорення
    $files = glob($dir . "/" . $ean . "*");
    if (!$files) return;

    foreach ($files as $filepath) {
      $file = basename($filepath);
      $name_file = explode(".", $file)[0];
      $parts = explode("_", $name_file);
      $is_additional = (count($parts) > 1);
      $base_name = $is_additional ? $parts[0] : $name_file;

      if ($ean == $base_name) {
        $image_path = "catalog/product/" . $file;

        if ($is_additional) {
          // Додаткові зображення (_1, _2, _n) - завжди в product_image
          $this->insertProductImage($prod_id, $image_path);
          $massFindProds[] = $image_path;
        } else {
          // Головне зображення (без _)
          $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($image_path) . "' WHERE product_id = '" . (int)$prod_id . "'");
          
          // Якщо немає опцій - також додаємо в додаткові зображення (product_image)
          if (!$has_options) {
            $this->insertProductImage($prod_id, $image_path);
            $massFindProds[] = $image_path;
          }
        }
      }
    }

    // Очищення старих зображень тільки для ПОТОЧНОГО штрихкоду
    // Щоб не видалити зображення інших варіантів цього ж товару
    $escaped_ean = $this->db->escape("catalog/product/" . $ean);
    $sql_delete = "DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$prod_id . "' AND (image = '" . $escaped_ean . ".jpg' OR image = '" . $escaped_ean . ".png' OR image = '" . $escaped_ean . ".jpeg' OR image LIKE '" . $escaped_ean . "\_%')";
    if (!empty($massFindProds)) {
      $sql_delete .= " AND image NOT IN ('" . implode("','", array_map([$this->db, 'escape'], $massFindProds)) . "')";
    }
    $this->db->query($sql_delete);
  }

  private function insertProductImage($product_id, $image)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' AND image = '" . $this->db->escape($image) . "'");
    if (!$query->num_rows) {
      $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($image) . "', sort_order = '0'");
    }
  }

  private function setOption($name, $type, $guid)
  {
    $sql = "SELECT `o`.`option_id`, `o`.`type`, `o`.`sort_order`, `od`.`slug` FROM `" . DB_PREFIX . "option` `o` LEFT JOIN `" . DB_PREFIX . "option_description` `od` ON (`o`.`option_id` = `od`.`option_id`)";
    $where = " WHERE `od`.`name` = '" . $this->db->escape($name) . "' AND `od`.`language_id` = " . $this->LANG_ID;

    $query = $this->query($sql . $where);
    if ($query->num_rows) {

      $option_id = $query->row['option_id'];

      $update_fields = array();
      if ($query->row['type'] != $type) {
        $update_fields[] = "`type` = '" . $type . "'";
      }

      $sql_fields = implode(', ', $update_fields);
      if ($sql_fields) {
        $this->query("UPDATE `" . DB_PREFIX . "option` SET " . $sql_fields . " WHERE `option_id` = " . (int)$option_id);
      }

      $slug = $query->row['slug'];
      if (empty($slug)) {
        $slug = $this->translit($name);
        $this->query("UPDATE `" . DB_PREFIX . "option_description` SET `slug` = '" . $slug . "' WHERE `option_id` = '" . (int)$option_id . "' AND `language_id` = " . $this->LANG_ID);
      }

    } else {
      // Если опции нет, добавляем
      $option_id = $this->addOption($name, $type, $guid);
    }
    return $option_id;
  }

  private function setOptionValue($value, $option_id, $image = '', $sort_order = '', $description = '', $composition = '')
  {

    $option_value_id = 0;

    $data = array();
    if ($sort_order) {
      $data['sort_order'] = $sort_order;
    }
    if ($image) {
      $data['image'] = $image;
    }

    // Проверим есть ли такое значение
    $query = $this->query("SELECT `ovd`.`option_value_id`,`ov`.`sort_order`,`ov`.`image` FROM `" . DB_PREFIX . "option_value_description` `ovd` LEFT JOIN `" . DB_PREFIX . "option_value` `ov` ON (`ovd`.`option_value_id` = `ov`.`option_value_id`) WHERE `ovd`.`language_id` = " . $this->LANG_ID . " AND `ovd`.`option_id` = " . $option_id . " AND `ovd`.`name` = '" . $this->db->escape($value) . "'");
    if ($query->num_rows) {

      $option_value_id = $query->row['option_value_id'];

      $this->log("Найдено значение опции '" . $value . "', option_value_id = " . $option_value_id, 2);

      // Сравнивает запрос с массивом данных и формирует список измененных полей
      $fields = $this->compareArrays($query, $data);

      // Если есть расхождения, производим обновление
      if ($fields) {
        $this->query("UPDATE `" . DB_PREFIX . "option_value` SET " . $fields . " WHERE `option_value_id` = " . (int)$option_value_id);
        $this->log("Значение опции обновлено: '" . $value . "'");
      }

      return $option_value_id;
    }

    $sql = $sort_order == "" ? "" : ", `sort_order` = " . (int)$sort_order;
    $query = $this->query("INSERT INTO `" . DB_PREFIX . "option_value` SET `option_id` = " . (int)$option_id . ", `image` = '" . $this->db->escape($image) . "'" . $sql);
    $option_value_id = $this->db->getLastId();

    if ($option_value_id) {
      $query = $this->query("INSERT INTO `" . DB_PREFIX . "option_value_description` SET `option_id` = " . (int)$option_id . ", `option_value_id` = " . (int)$option_value_id . ", `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($value) . "'");
      $this->log("Значение опции добавлено: '" . $value . "', option_value_id = " . $option_value_id);
    }

    return $option_value_id;

  }

  private function getOrganizationByUid($uid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "organization WHERE uid = '" . $uid . "'");
    return $query->row;
  }

  private function addOrganization($name, $guid)
  {
    $current_date = date('Y-m-d H:i:s');
    $this->query("INSERT INTO `" . DB_PREFIX . "organization` SET `uid` = '" . $guid . "', `sort_order` = 0, `date_modified` = '" . $current_date . "', `date_added` = '" . $current_date . "'");
    $organization_id = $this->db->getLastId();

    $this->query("INSERT INTO `" . DB_PREFIX . "organization_description` SET `organization_id` = '" . (int)$organization_id . "', `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($name) . "'");

    return $organization_id;
  }

  private function updateOrganization($organization_id, $data)
  {
    $current_date = date('Y-m-d H:i:s');
    $this->query("UPDATE `" . DB_PREFIX . "organization_description` SET name = '" . $this->db->escape($data['name']) . "', `date_modified` = '" . $current_date . "' WHERE `organization_id` = '" . (int)($organization_id) . "'");
  }

  private function updateCustomerAdressDefault($customer_id, $adress_id)
  {
    $this->query("UPDATE `" . DB_PREFIX . "customer` SET address_id = '" . (int)$adress_id . "' WHERE `customer_id` = '" . (int)$customer_id . "'");
  }

  private function addOption($name, $type, $guid)
  {

    $this->query("INSERT INTO `" . DB_PREFIX . "option` SET `type` = '" . $type . "', `guid` = '" . $guid . "', `sort_order` = 0");
    $option_id = $this->db->getLastId();

    $slug = $this->translit($name);
    $this->query("INSERT INTO `" . DB_PREFIX . "option_description` SET `option_id` = '" . (int)$option_id . "', `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($name) . "', `slug` = '" . $slug . "'");

    return $option_id;
  }

  public function getAddresses($customer_id)
  {
    $address_data = array();

    $query = $this->db->query("SELECT address_id FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "'");

    foreach ($query->rows as $result) {
      $address_info = $this->getAddress($result['address_id']);

      if ($address_info) {
        $address_data[$result['address_id']] = $address_info;
      }
    }

    return $address_data;
  }

  public function getAddress($address_id)
  {
    $address_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "'");

    if ($address_query->num_rows) {

      return array(
        'address_id' => $address_query->row['address_id'],
        'customer_id' => $address_query->row['customer_id'],
        'firstname' => $address_query->row['firstname'],
        'lastname' => $address_query->row['lastname'],
        'company' => $address_query->row['company'],
        'address_1' => $address_query->row['address_1'],
        'address_2' => $address_query->row['address_2'],
        'postcode' => $address_query->row['postcode'],
        'city' => $address_query->row['city'],
        'type' => $address_query->row['type'],
        'customer_cod_guid' => $address_query->row['customer_cod_guid'],
        'np_city_id' => $address_query->row['np_city_id'],
        'np_city_name' => $address_query->row['np_city_name'],
        'np_street_id' => $address_query->row['np_street_id'],
        'np_street_name' => $address_query->row['np_street_name'],
        'np_post_id' => $address_query->row['np_post_id'],
        'np_post_name' => $address_query->row['np_post_name'],
        'np_house' => $address_query->row['np_house'],
        'np_level' => $address_query->row['np_level'],
        'np_apartment' => $address_query->row['np_apartment'],
        'np_customer_name' => $address_query->row['np_customer_name'],
        'np_customer_lastname' => $address_query->row['np_customer_lastname'],
        'np_customer_phone' => $address_query->row['np_customer_phone'],
        'zone_id' => $address_query->row['zone_id'],
        'country_id' => $address_query->row['country_id'],
        'custom_field' => json_decode($address_query->row['custom_field'], true)
      );
    }
  }

  private function getProductOptions($product_id)
  {

    $data = array();
    // Запрос без связи опции к товару
    $query_option = $this->query("SELECT `po`.`option_id`, `po`.`product_option_id`, `od`.`name`, `po`.`required` FROM `" . DB_PREFIX . "product_option` `po` LEFT JOIN `" . DB_PREFIX . "option_description` `od` ON (`po`.`option_id` = `od`.`option_id`) WHERE `po`.`product_id` = " . (int)$product_id . " AND `od`.`language_id` = " . $this->LANG_ID);

    if ($query_option->num_rows) {
      // Получим значения этих опций
      foreach ($query_option->rows as $row_option) {

        $product_option_id = $row_option['product_option_id'];
        $data[$product_option_id] = array(
          'product_option_id' => $row_option['product_option_id'],
          'option_id' => $row_option['option_id'],
          'name' => $row_option['name'],
          'required' => $row_option['required']
        );

        $query_value = $this->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` `pov` LEFT JOIN `" . DB_PREFIX . "option_value_description` `ovd` ON (`pov`.`option_value_id` = `ovd`.`option_value_id`) WHERE `pov`.`product_option_id` = " . (int)$product_option_id . " AND `ovd`.`language_id` = " . $this->LANG_ID);

        if ($query_value->num_rows) {
          $values = array();

          foreach ($query_value->rows as $row_value) {
            $values[$row_value['product_option_value_id']] = array(
              'product_option_value_id' => $row_value['product_option_value_id'],
              'option_value_id' => $row_value['option_value_id'],
              'name' => $row_value['name'],
              'subtract' => $row_value['subtract'],
              'price' => $row_value['price'],
              'price_base' => $row_value['price_base'],
              'price_prefix' => $row_value['price_prefix'],
              'points' => $row_value['points'],
              'points_prefix' => $row_value['points_prefix'],
              'weight' => $row_value['weight'],
              'weight_prefix' => $row_value['weight_prefix'],
              'product_status_id' => $row_value['product_status_id']
            );
          }
          $data[$product_option_id]['values'] = $values;
        }

      }
    }
    return $data;
  }

  private function addProductCategories($product_id, $category_id)
  {
    //дополнительные категории
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id='" . $category_id . "' ORDER by path_id ASC");
    if ($query->num_rows) {
      foreach ($query->rows as $row) {
        if ($category_id == (int)$row["path_id"]) {
          $this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = '" . (int)$product_id . "', `category_id` = '" . (int)$row["path_id"] . "', `main_category` = 1");
        } else {
          $this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = '" . (int)$product_id . "', `category_id` = '" . (int)$row["path_id"] . "', `main_category` = 0");
        }

      }
    }
    return true;
  }

  private function prepareQuery($data, $mode = 'set', $table = '')
  {

    // Удаляет поля которых нет в указанной таблице
    if ($table) {
      $query = $this->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "`");
      $fields = array();
      if ($query->num_rows) {
        foreach ($query->rows as $row) {
          $fields[$row['Field']] = $row['Type'];
        }
        $this->log($fields, 2);
        $this->log($data, 2);
      }
      foreach ($data as $field => $row) {
        if (is_array($data) && !isset($fields[$field])) {
          unset($data[$field]);
          $this->log("Удалено поле " . $field);
        }
      }
    }

    // Формируем строку запроса
    $sql = array();
    foreach ($data as $field => $value) {
      $sql[] = $mode == 'set' ? "`" . $field . "` = " . (is_numeric($value) ? $value : "'" . $this->db->escape($value) . "'") : "`" . $field . " `";
    }

    return implode(($mode = 'set' ? ', ' : ' AND '), $sql);

  }

  private function prepareQueryProduct($data, $mode = 'set')
  {
    $sql = array();
    if (isset($data['code']))
      $sql[] = $mode == 'set' ? "`model` = '" . $this->db->escape($data['code']) . "'" : "`model`";
    if (isset($data['id']))
      $sql[] = $mode == 'set' ? "`jan` = '" . $this->db->escape($data['id']) . "'" : "`jan`";
    if (isset($data['location']))
      $sql[] = $mode == 'set' ? "`location` = '" . $this->db->escape($data['location']) . "'" : "`location`";
    if (isset($data['minimum']))
      $sql[] = $mode == 'set' ? "`minimum` = '" . (float)$data['minimum'] . "'" : "`minimum`";
    if (isset($data['subtract']))
      $sql[] = $mode == 'set' ? "`subtract` = '" . (int)$data['subtract'] . "'" : "`subtract`";
    if (isset($data['stock_status_id']))
      $sql[] = $mode == 'set' ? "`stock_status_id` = '" . (int)$data['stock_status_id'] . "'" : "`stock_status_id`";
    if (isset($data['date_available']))
      $sql[] = $mode == 'set' ? "`date_available` = '" . $this->db->escape($data['date_available']) . "'" : "`date_available`";
    if (isset($data['manufacturer_id']))
      $sql[] = $mode == 'set' ? "`manufacturer_id` = '" . (int)$data['manufacturer_id'] . "'" : "`manufacturer_id`";
    if (isset($data['shipping']))
      $sql[] = $mode == 'set' ? "`shipping` = '" . (int)$data['shipping'] . "'" : "`shipping`";
    if (isset($data['points']))
      $sql[] = $mode == 'set' ? "`points` = '" . (int)$data['points'] . "'" : "`points`";
    if (isset($data['length']))
      $sql[] = $mode == 'set' ? "`length` = '" . (float)$data['length'] . "'" : "`length`";
    if (isset($data['width']))
      $sql[] = $mode == 'set' ? "`width` = '" . (float)$data['width'] . "'" : "`width`";
    if (isset($data['weight']))
      $sql[] = $mode == 'set' ? "`weight` = '" . (float)$data['weight'] . "'" : "`weight`";
    if (isset($data['height']))
      $sql[] = $mode == 'set' ? "`height` = '" . (float)$data['height'] . "'" : "`height`";
    if (isset($data['status']))
      $sql[] = $mode == 'set' ? "`status` = '" . (int)$data['status'] . "'" : "`status`";
    if (isset($data['noindex']))
      $sql[] = $mode == 'set' ? "`noindex` = '" . (int)$data['noindex'] . "'" : "`noindex`";
    if (isset($data['tax_class_id']))
      $sql[] = $mode == 'set' ? "`tax_class_id` = '" . (int)$data['tax_class_id'] . "'" : "`tax_class_id`";
    if (isset($data['sort_order']))
      $sql[] = $mode == 'set' ? "`sort_order` = '" . (int)$data['sort_order'] . "'" : "`sort_order`";
    if (isset($data['length_class_id']))
      $sql[] = $mode == 'set' ? "`length_class_id` = '" . (int)$data['length_class_id'] . "'" : "`length_class_id`";
    if (isset($data['weight_class_id']))
      $sql[] = $mode == 'set' ? "`weight_class_id` = '" . (int)$data['weight_class_id'] . "'" : "`weight_class_id`";
    if (isset($data['image']))
      $sql[] = $mode == 'set' ? "`image` = '" . $this->db->escape($data['image']) . "'" : "`image`";

    return implode(($mode = 'set' ? ', ' : ' AND '), $sql);

  }

  private function setManufacturer($name, $guid = '')
  {
    $this->log('Производитель: ' . $name, 2);
    $manufacturer_id = 0;

    if (empty($this->MANUFACTURERS)) {
      $this->MANUFACTURERS = $this->getManufacturers();
    }
    //$this->log($this->MANUFACTURERS, 2);

    foreach ($this->MANUFACTURERS as $manufacturer_id => $manufacturer_data) {
      if ($guid) {
        if ($name == $manufacturer_data['name'] && $guid == $manufacturer_data['guid']) {
          $this->log("Найден производитель manufacturer_id = " . $manufacturer_id . ", Ид = " . $guid, 2);
          return $manufacturer_id;
        }
      } else {
        if ($name == $manufacturer_data['name']) {
          $this->log("Найден производитель manufacturer_id = " . $manufacturer_id, 2);
          return $manufacturer_id;
        }
      }
    }

    $this->log('Производитель не найден, добавляем...', 2);
    // Добавим производителя
    $data = array(
      'name' => trim($name),
      'sort_order' => 0,
      'guid' => $guid,
      'description' => ''
    );
    $manufacturer_id = $this->addManufacturer($data);

    return $manufacturer_id;
  }

  private function getManufacturers()
  {

    if (isset($this->TAB_FIELDS['manufacturer_description']['name'])) {
      $query = $this->query("SELECT `m`.`manufacturer_id`, `m`.`name`, `m1c`.`guid` FROM `" . DB_PREFIX . "manufacturer_description` `m` LEFT JOIN `" . DB_PREFIX . "manufacturer_to_1c` `m1c` ON (`m`.`manufacturer_id` = `m1c`.`manufacturer_id`) LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` `ms` ON (`m`.`manufacturer_id` = `ms`.`manufacturer_id`) WHERE `ms`.`store_id` = " . (int)$this->STORE_ID);
      //$query = $this->query("SELECT `manufacturer_id`, `name` FROM `" . DB_PREFIX . "manufacturer_description` LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` `ms` ON (`m`.`manufacturer_id` = `ms`.`manufacturer_id`) WHERE `ms`.`store_id` = " . (int)$this->STORE_ID);
    } else {
      $query = $this->query("SELECT `m`.`manufacturer_id`, `m`.`name`, `m1c`.`guid` FROM `" . DB_PREFIX . "manufacturer` `m` LEFT JOIN `" . DB_PREFIX . "manufacturer_to_1c` `m1c` ON (`m`.`manufacturer_id` = `m1c`.`manufacturer_id`) LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` `ms` ON (`m`.`manufacturer_id` = `ms`.`manufacturer_id`) WHERE `ms`.`store_id` = " . (int)$this->STORE_ID);
      //$query = $this->query("SELECT `manufacturer_id`, `name` FROM `" . DB_PREFIX . "manufacturer` `m` LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` `ms` ON (`m`.`manufacturer_id` = `ms`.`manufacturer_id`) WHERE `ms`.`store_id` = " . (int)$this->STORE_ID);
    }

    $data = array();

    foreach ($query->rows as $row) {
      $data[$row['manufacturer_id']] = array(
        'name' => $row['name'],
        'guid' => $row['guid']
      );
      //$data[$row['manufacturer_id']] = $row['name'];

    } // foreach

    $this->log("Производителей всего в базе: " . count($data));

    return $data;

  }

  private function addManufacturer($data)
  {

    $this->query("INSERT INTO `" . DB_PREFIX . "manufacturer` SET `guid` = '" . $this->db->escape($data['guid']) . "', `name` = '" . $this->db->escape($data['name']) . "', `noindex` = 1");
    $manufacturer_id = $this->db->getLastId();

    $query = "manufacturer_id=" . (int)$manufacturer_id;
    if (isset($data['name'])) {

      $keyword_new = $this->translit(trim($data['name']));
      $url = $this->uniqueSlug((int)$this->STORE_ID, (int)$this->LANG_ID, $keyword_new, $query);

      if ($url == "api") {
        $url = "appi";
      }

      $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'manufacturer_id=" . (int)$manufacturer_id . "', keyword = '" . $this->db->escape($url) . "', language_id = '" . (int)$this->LANG_ID . "', store_id = '" . $this->STORE_ID . "'");
    }

    //ОПИСАНИЕ ПРОИЗВОДИТЕЛЯ
    if (is_array($data)) {
      unset($data['name']);
    }
    $sql = $this->prepareQueryDescription($data, "set");
    if ($sql) {
      $this->query("INSERT INTO `" . DB_PREFIX . "manufacturer_description` SET " . $sql . ", `language_id` = " . $this->LANG_ID . ", `manufacturer_id` = " . (int)$manufacturer_id);
    } else {
      $this->query("INSERT INTO `" . DB_PREFIX . "manufacturer_description` SET `manufacturer_id` = " . (int)$manufacturer_id . ", `language_id` = " . $this->LANG_ID . ", `description` = ''");
    }


    //МАГАЗИН
    $this->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` SET `manufacturer_id` = " . (int)$manufacturer_id . ", `store_id` = " . $this->STORE_ID);
    return $manufacturer_id;
  }

  private function updateManufacturer($manufacturer_id, $data)
  {
    $this->query("UPDATE `" . DB_PREFIX . "manufacturer` SET `name` = '" . $this->db->escape(trim($data['name'])) . "' WHERE `manufacturer_id` = '" . (int)$manufacturer_id . "'");
    if (isset($data['description'])) {
      $this->query("UPDATE `" . DB_PREFIX . "manufacturer_description` SET `description` = '" . $this->db->escape($data['description']) . "' WHERE `manufacturer_id` = '" . (int)$manufacturer_id . "' AND `language_id` = '" . $this->LANG_ID . "'");
    }
  }

  private function addWarehouse($data)
  {

    if (!isset($data['image'])) $data['image'] = "";
    if (!isset($data['sort_order'])) $data['sort_order'] = 1;
    if (!isset($data['status'])) $data['status'] = 1;
    if (!isset($data['phone'])) $data['phone'] = "";
    if (!isset($data['address'])) $data['address'] = "";
    if (!isset($data['country'])) $data['country'] = "";
    if (!isset($data['city'])) $data['city'] = "";
    if (!isset($data['working_hours'])) $data['working_hours'] = "";

    foreach ($data["values"] as $val) {
      $data['phone'] = $val['phone'];
      $data['address'] = $val['address'];
      $data['country'] = $val['country'];
      $data['city'] = $val['city'];
      $data['description'] = $val['description'];
    }
    $current_date = date('Y-m-d H:i:s');
    $this->query("INSERT INTO `" . DB_PREFIX . "warehouse` SET `uid` = '" . $data['id'] . "', `image` = '" . $this->db->escape($data['image']) . "', `phone` = '" . $data['phone'] . "', `sort_order` = '" . (int)$data['sort_order'] . "', `status` = '" . (int)$data['status'] . "', `date_added` = '" . $current_date . "', `date_modified` = '" . $current_date . "'");
    $warehouse_id = $this->db->getLastId();

    $query = "warehouse_id=" . (int)$warehouse_id;

    if (isset($data['name'])) {
      $keyword_new = $this->translit($data['name']);
      $url = $this->uniqueSlug((int)$this->STORE_ID, (int)$this->LANG_ID, $keyword_new, $query);
      $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET query = 'warehouse_id=" . (int)$warehouse_id . "', keyword = '" . $this->db->escape($url) . "', language_id = '" . $this->LANG_ID . "', store_id = '" . $this->STORE_ID . "'");
    }

    $this->query("INSERT INTO `" . DB_PREFIX . "warehouse_description` SET warehouse_id = '" . (int)$warehouse_id . "', language_id = '" . $this->LANG_ID . "', `name` = '" . $this->db->escape($data['name']) . "', address = '" . $this->db->escape($data['address']) . "', working_hours = '" . $this->db->escape($data['working_hours']) . "'");

    if ($data['id'] == "B") {
      $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $warehouse_id . "', serialized = '0'  WHERE `code` = config AND `key` = config_warehouse_id AND store_id = '" . $this->STORE_ID . "'");
    }


    return $warehouse_id;
  }

  private function updateWarehouse(&$warehouse, $data)
  {

    $warehouse_id = $warehouse["warehouse_id"];

    if (isset($data['name'])) $warehouse['name'] = $data['name'];

    $warehouse['working_hours'] = $warehouse["working_hours"];
    $warehouse['address'] = $warehouse["address"];
    $warehouse['phone'] = $warehouse["phone"];

    foreach ($data["values"] as $val) {
      if (isset($val['phone'])) {
        $warehouse['phone'] = $val['phone'];
      }
      if (isset($val['address'])) {
        $warehouse['address'] = $val['address'];
      }
      if (isset($val['working_hours'])) {
        $warehouse['working_hours'] = $val['working_hours'];
      }
    }
    $current_date = date('Y-m-d H:i:s');
    $this->query("UPDATE `" . DB_PREFIX . "warehouse` SET date_modified = '" . $current_date . "', `phone` = '" . $this->db->escape($warehouse['phone']) . "' WHERE `organization_id` = '" . (int)$warehouse_id . "'");
    $this->query("UPDATE `" . DB_PREFIX . "warehouse_description` SET `address` = '" . $this->db->escape($warehouse['address']) . "', `working_hours` = '" . $this->db->escape($warehouse['working_hours']) . "', `address` = '" . $this->db->escape($warehouse['address']) . "' WHERE `warehouse_id` = '" . (int)$warehouse_id . "' AND `language_id` = '" . $this->LANG_ID . "'");

    unset($warehouse);
  }

  private function getStatusProduct($name)
  {
    $query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_status` WHERE `name` = '" . $this->db->escape($name) . "' AND `language_id` = '" . (int)$this->LANG_ID . "'");
    return $query->row;
  }

  private function addStatusProduct($name, $code)
  {
    $this->query("INSERT INTO `" . DB_PREFIX . "product_status` SET `name` = '" . $this->db->escape($name) . "', `code` = '" . $this->db->escape($code) . "', `language_id` = '" . $this->LANG_ID . "'");
    return $this->db->getLastId();
  }

  private function getWarehouseByUid($uid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "warehouse c LEFT JOIN " . DB_PREFIX . "warehouse_description cd2 ON (c.warehouse_id = cd2.warehouse_id) WHERE c.uid = '" . $this->db->escape($uid) . "' AND cd2.language_id = '" . (int)$this->LANG_ID . "'");
    return $query->row;
  }

  private function getWarehouses()
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "warehouse");
    return $query->rows;
  }

  private function getWarehouse($warehouse_id)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "warehouse c LEFT JOIN " . DB_PREFIX . "warehouse_description cd2 ON (c.warehouse_id = cd2.warehouse_id) WHERE c.warehouse_id = '" . (int)$warehouse_id . "' AND cd2.language_id = '" . (int)$this->LANG_ID . "'");
    return $query->row;
  }


  private function getCardByUid($uid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "cards c WHERE c.guid = '" . $uid . "'");
    return $query->row;
  }

  private function getGroupByUid($uid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "customer_group cg LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (cgd.customer_group_id = cg.customer_group_id) WHERE cg.guid = '" . $this->db->escape($uid) . "' AND cgd.language_id = '" . (int)$this->LANG_ID . "'");
    return $query->row;
  }

  private function getAddressClientByUid($uid, $customer_id, $customer_code_1c)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "address WHERE guid = '" . $this->db->escape($uid) . "' AND customer_id = '" . (int)$customer_id . "' AND customer_cod_guid = '" . $this->db->escape($customer_code_1c) . "'");
    return $query->row;
  }

  private function getCategoryByUid($uid)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category c WHERE c.guid = '" . $uid . "'");
    return $query->row;
  }

  private function getCategory($category_id)
  {
    $query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd2.language_id = '" . (int)$this->LANG_ID . "'");
    return $query->row;
  }

  private function getCategories()
  {
    $query = $this->query("SELECT category_id FROM " . DB_PREFIX . "category WHERE type = 0");
    return $query->rows;
  }

  private function getSubCategories()
  {
    $query = $this->query("SELECT category_id FROM " . DB_PREFIX . "category WHERE type = 1");
    return $query->rows;
  }

  private function addCategory($data)
  {

    if (!isset($data['top'])) $data['top'] = 1;
    if (!isset($data['column'])) $data['column'] = 1;
    if (!isset($data['sort_order'])) $data['sort_order'] = 1;
    if (!isset($data['status'])) $data['status'] = 1;

    $parent_id = 0;
    if ($data["level"] > 1) {
      foreach ($data["child_categories"] as $child) {
        $parent = $this->getCategoryByUid($child);
        if ($parent != null) {
          if (isset($parent['category_id'])) {
            $parent_id = $parent['category_id'];
          }
        }
      }
    }
    $current_date = date('Y-m-d H:i:s');
    $this->query("INSERT INTO `" . DB_PREFIX . "category` SET `guid` = '" . $data['id'] . "', `parent_id` = " . (int)$parent_id . ", `top` = " . (int)$data['top'] . ", `column` = " . (int)$data['column'] . ", `type` = " . (int)$data['type'] . ", `sort_order` = " . (int)$data['sort_order'] . ", `status` = " . (int)$data['status'] . ", `date_added` = '" . $current_date . "', `date_modified` = '" . $current_date . "'");
    $category_id = $this->db->getLastId();

    $this->addCategoryImage($category_id, $data['id']);

    $query = "category_id=" . (int)$category_id;

    if (isset($data['name'])) {

      //якщо однакові назви товару то додаємо категорію
      $parent_category_slug = '';
      if ($parent_id > 0) {
        $query_cat = "category_id=" . $parent_id;
        $slug = $this->getSlug((int)$this->STORE_ID, (int)$this->LANG_ID, $query_cat);
        if ($slug != null) {
          $parent_category_slug = $slug['keyword'];
        }
      }

      $keyword_new = $this->translit(trim($data['name']));

      $url = $this->uniqueSlug((int)$this->STORE_ID, (int)$this->LANG_ID, $keyword_new, $query, $parent_category_slug);
      $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($url) . "', language_id = '" . (int)$this->LANG_ID . "', store_id = '" . (int)$this->STORE_ID . "'");
    }

    // Описание категории
    if (!isset($data['description'])) $data['description'] = "";
    if (!isset($data['meta_title'])) $data['meta_title'] = "";
    if (!isset($data['meta_description'])) $data['meta_description'] = "";
    if (!isset($data['meta_keyword'])) $data['meta_keyword'] = "";

    $this->query("INSERT INTO `" . DB_PREFIX . "category_description` SET `category_id` = " . (int)$category_id . ", `language_id` = " . (int)$this->LANG_ID . ", `name` = '" . $this->db->escape($data['name']) . "', `description` = '" . $this->db->escape($data['description']) . "', `meta_title` = '" . $this->db->escape($data['meta_title']) . "', `meta_description` = '" . $this->db->escape($data['meta_description']) . "', `meta_keyword` = '" . $this->db->escape($data['meta_keyword']) . "'");

    $level = 0;
    $query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$parent_id . "' ORDER BY `level` ASC");

    foreach ($query->rows as $result) {
      $this->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");
      $level++;
    }

    $this->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");

    // Магазин
    $this->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = " . (int)$category_id . ",  store_id = " . (int)$this->STORE_ID);

    return $category_id;

  }

  private function updateCategory($category_id, $data)
  {

    $old = $this->getCategory($category_id);
    $data = array_merge($old, $data);

    // Указываем поля которые не нужно обновлять
    $no_update_fields = array();

    // Надо проверить поля
    $data_update = $this->compareArraysData($data, $old, $no_update_fields);

    if ($data_update) {

      $update = false;

      // Если было обновлено описание
      $fields = $this->prepareQueryDescription($data_update);

      if ($fields) {
        $this->query("UPDATE `" . DB_PREFIX . "category_description` SET " . $fields . " WHERE `category_id` = " . (int)$category_id . " AND `language_id` = " . $this->LANG_ID);
        $update = true;
      }

      $fields_category = $this->prepareQueryCategory($data_update);

      if ($update || $fields_category) {
        $current_date = date('Y-m-d H:i:s');
        $this->query("UPDATE `" . DB_PREFIX . "category` SET " . $fields_category . "`date_modified` = '" . $current_date . "' WHERE `category_id` = " . (int)$category_id);
        $this->log("Обновлена категория '" . $data['name'] . "'", 2);

        // Обновляем иерархию, если поменялась позиция
        $parent_id = 0;
        if ($data["level"] > 1) {
          foreach ($data["child_categories"] as $child) {
            $parent_id = $this->getCategoryByUid($child)['category_id'];
          }
        }
        if ($parent_id != $old['parent_id']) {
          // Изменилась структура, нужно обновить иерархию
          $this->updateHierarchical($category_id, $parent_id);
        }
      } else {
        $this->log("После подготовки данных нечего обновлять, возможно тут ошибка");
      }
    } else {
      $this->log("Нет изменений", 2);
    }
    $this->addCategoryImage($category_id, $data['id']);
  }

  private function addCategoryImage($category_id, $code)
  {
    $dir = DIR_IMAGE . "/catalog/category-image";
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
      $name_file = explode(".", $file)[0];
      //якщо це додаткове зображення
      if ($code == $name_file) {
        $this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape("catalog/category-image/" . $file) . "' WHERE category_id = '" . (int)$category_id . "'");
      }
    }
  }


  private function compareArrays($query, $data, $no_update = array())
  {
    // Сравниваем значения полей, если есть изменения, формируем поля для запроса
    $upd_fields = array();
    if ($query->num_rows) {
      foreach ($query->row as $key => $row) {
        if (!isset($data[$key]) || isset($no_update[$key])) continue;
        if ($row <> $data[$key]) {
          $upd_fields[] = "`" . $key . "` = '" . $this->db->escape($data[$key]) . "'";
          $this->log("[i] Отличается поле '" . $key . "', старое: '" . $row . "', новое: '" . $data[$key] . "'", 2);
        }
      }
    }
    return implode(', ', $upd_fields);
  }

  private function compareArraysData(&$data_new, $data_old, $ignore_fields = array(), $merge = true)
  {
    $result = array();

    if (count($data_old)) {
      foreach ($data_old as $field => $value) {
        if (!isset($data_new[$field])) {
          // Если включено объединение, то записываем в новый массив старые данные полей которых нет в новом
          if ($merge) {
            $data_new[$field] = $value;
          }
          continue;
        }

        // Пропускаем те поля которые не нужно обновлять
        if ($ignore_fields) {
          $key = array_search($field, $ignore_fields);
          if ($key !== false) {
            continue;
          }
        }

        if ($value != $data_new[$field]) {
          $result[$field] = $this->db->escape($data_new[$field]);
        }
      }
    }
    return $result;
  }

  /**
   * Замінює прямі лапки "" на лапки-ялинки «» у рядку.
   * Використовувати для назв товарів та інших текстів, де потрібні типографічні лапки.
   *
   * @param string $str Рядок для обробки
   * @return string Рядок з заміненими лапками
   */
  private function replaceQuotesWithGuillemets($str)
  {
    if (!is_string($str) || $str === '') {
      return $str;
    }
    $n = 0;
    return preg_replace_callback('/"/u', function () use (&$n) {
      return ($n++ % 2 === 0) ? '«' : '»';
    }, $str);
  }

  private function prepareQueryDescription($data, $mode = 'set')
  {

    $sql = array();

    if (isset($data['name'])) {
      $name = $this->replaceQuotesWithGuillemets($data['name']);
      $sql[] = $mode == 'set' ? "`name` = '" . $this->db->escape($name) . "'" : "`name`";
    }

    if (isset($data['composition']))
      $sql[] = $mode == 'set' ? "`composition` = '" . $this->db->escape($data['composition']) . "'" : "`composition`";
    if (isset($data['normi']))
      $sql[] = $mode == 'set' ? "`normi` = '" . $this->db->escape($data['normi']) . "'" : "`normi`";

    if (isset($data['description']))
      $sql[] = $mode == 'set' ? "`description` = '" . $this->db->escape($data['description']) . "'" : "`description`";
    if (isset($data['meta_title']))
      $sql[] = $mode == 'set' ? "`meta_title` = '" . $this->db->escape($data['meta_title']) . "'" : "`meta_title`";
    if (isset($data['meta_h1']))
      $sql[] = $mode == 'set' ? "`meta_h1` = '" . $this->db->escape($data['meta_h1']) . "'" : "`meta_h1`";
    if (isset($data['meta_description']))
      $sql[] = $mode == 'set' ? "`meta_description` = '" . $this->db->escape($data['meta_description']) . "'" : "`meta_description`";
    if (isset($data['meta_keyword']))
      $sql[] = $mode == 'set' ? "`meta_keyword` = '" . $this->db->escape($data['meta_keyword']) . "'" : "`meta_keyword`";
    if (isset($data['tag']))
      $sql[] = $mode == 'set' ? "`tag` = '" . $this->db->escape($data['tag']) . "'" : "`tag`";

    return implode(($mode = 'set' ? ', ' : ' AND '), $sql);

  }

  private function prepareQueryCategory($data, $mode = 'set')
  {

    $sql = array();

    if (isset($data['catalog'])) {
      $sql[] = $mode == 'set' ? "`catalog` = '" . $this->db->escape($data['catalog']) . "'" : "`catalog`";
    }
    if (isset($data['top']))
      $sql[] = $mode == 'set' ? "`top` = " . (int)$data['top'] : "top";
    if (isset($data['column']))
      $sql[] = $mode == 'set' ? "`column` = " . (int)$data['column'] : "column";
    if (isset($data['type']))
      $sql[] = $mode == 'set' ? "`type` = " . (int)$data['type'] : "type";
    if (isset($data['sort_order']))
      $sql[] = $mode == 'set' ? "`sort_order` = " . (int)$data['sort_order'] : "sort_order";
    if (isset($data['status']))
      $sql[] = $mode == 'set' ? "`status` = " . (int)$data['status'] : "status";
    if (isset($data['noindex']))
      $sql[] = $mode == 'set' ? "`noindex` = " . (int)$data['noindex'] : "noindex";
    if (isset($data['parent_id']))
      $sql[] = $mode == 'set' ? "`parent_id` = " . (int)$data['parent_id'] : "parent_id";

    $result = implode(($mode = 'set' ? ', ' : ' AND '), $sql);
    return $result ? $result . ", " : "";

  }

  private function updateHierarchical($category_id, $parent_id)
  {

    // MySQL Hierarchical Data Closure Table Pattern
    $query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `path_id` = " . (int)$category_id . " ORDER BY `level` ASC");

    if ($query->rows) {
      foreach ($query->rows as $category_path) {
        // Delete the path below the current one
        $this->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$category_path['category_id'] . " AND `level` < " . (int)$category_path['level']);

        $path = array();

        // Get the nodes new parents
        $query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$parent_id . " ORDER BY `level` ASC");

        foreach ($query->rows as $result) {
          $path[] = $result['path_id'];
        }

        // Get whats left of the nodes current path
        $query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$category_path['category_id'] . " ORDER BY `level` ASC");

        foreach ($query->rows as $result) {
          $path[] = $result['path_id'];
        }

        // Combine the paths with a new level
        $level = 0;

        foreach ($path as $path_id) {
          $this->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . (int)$category_path['category_id'] . ", `path_id` = " . (int)$path_id . ", `level` = " . $level);
          $level++;
        }
      }

    } else {
      // Delete the path below the current one
      $this->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$category_id);

      // Fix for records with no paths
      $level = 0;

      $query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$parent_id . " ORDER BY `level` ASC");

      foreach ($query->rows as $result) {
        $this->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . (int)$category_id . ", `path_id` = " . (int)$result['path_id'] . ", `level` = " . $level);
        $level++;
      }

      $this->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . (int)$category_id . ", `path_id` = " . (int)$category_id . ", `level` = " . $level);
    }

    $this->log("Обновлена иерархия у категории", 2);

  }

  public function getUnitByName($unit_name)
  {
    $query = $this->query("SELECT * FROM `" . DB_PREFIX . "length_class_description` WHERE `title` = '" . $unit_name . "'");
    return $query->row;
  }

  public function addUnitByName($unit_name)
  {
    $count = 1;
    $this->query("INSERT INTO `" . DB_PREFIX . "length_class` SET `value` = '" . (int)$count . "'");
    $unit_id = $this->db->getLastId();
    $this->query("INSERT INTO `" . DB_PREFIX . "length_class_description` SET `length_class_id` = '" . (int)$unit_id . "', language_id = '" . $this->LANG_ID . "', title='" . $unit_name . "', unit='" . $unit_name . "'");
    return $unit_id;
  }

  public function getLanguageId($lang)
  {
    if ($this->LANG_ID) {
      return $this->LANG_ID;
    }

    if (!$lang) {
      $lang = $this->config->get('config_language');
    }

    $query = $this->query("SELECT `language_id` FROM `" . DB_PREFIX . "language` WHERE `code` = '" . $this->db->escape($lang) . "'");
    if ($query->num_rows) {
      $this->LANG_ID = $query->row['language_id'];
    } else {
      $query = $this->query("SELECT `language_id` FROM `" . DB_PREFIX . "language` LIMIT 1");
      if ($query->num_rows) {
        $this->LANG_ID = $query->row['language_id'];
      }
    }
    return $this->LANG_ID;
  }

  private function translit($s, $space = '-')
  {
    $s = (string)$s; // преобразуем в строковое значение
    $s = strip_tags($s); // убираем HTML-теги
    $s = str_replace(array('\n', '\r'), ' ', $s); // убираем перевод каретки
    $s = trim($s); // убираем пробелы в начале и конце строки
    $s = str_replace('&quot;', '', $s); #mod убираем кавычки
    $s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); // переводим строку в нижний регистр (иногда надо задать локаль)
    $s = strtr($s, array('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ы' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 'ъ' => '', 'ь' => ''));
    $s = preg_replace('/[^0-9a-z-_ ]/i', '', $s); // очищаем строку от недопустимых символов
    $s = preg_replace('/\s+/', ' ', $s); // удаляем повторяющие пробелы
    $s = str_replace(' ', $space, $s); // заменяем пробелы знаком минус
    return $s; // возвращаем результат
  }

  private function getSeoUrl($element, $id, $last_symbol = "")
  {

    $result = array(
      'seo_url_id' => 0,
      'keyword' => ""
    );
    //#mod $query = $this->query("SELECT `seo_url_id`,`keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `query` = '" . $element . "=" . (string)$id . "'");

    switch ($element) {
      case 'path':
        $path = $this->getPath((int)$id);
        $query = $this->query("SELECT `seo_url_id`,`keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `query` = 'path=" . $this->db->escape($path) . "'");
        break;

      case 'product_id':
        $query = $this->query("SELECT `seo_url_id`,`keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `query` = '" . $element . "=" . (string)$id . "'");
        break;

      case 'manufacturer_id':
        $query = $this->query("SELECT `seo_url_id`,`keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `query` = '" . $element . "=" . (string)$id . "'");
        break;
    }

    if ($query->num_rows) {
      $result = array(
        'seo_url_id' => $query->row['seo_url_id'],
        'keyword' => $query->row['keyword'] . $last_symbol
      );
      return $result;
    }
    return $result;

  }

  private function setSeoURL($url_type, $element_id, $element_name, $old_element)
  {

    if (empty($old_element['keyword']) && empty($element_name)) {
      $this->log("ВНИМАНИЕ! старое и новое значение SEO URL пустое!");
      return false;
    }

    $this->log("SEO URL старое: '" . $old_element['keyword'] . "', новое '" . $element_name . "'", 2);

    // Проверка на одинаковые keyword
    $keyword = $element_name;

    // Получим все названия начинающиеся на $element_name
    $keywords = array();
    //#mod $query = $this->query("SELECT `seo_url_id`,`keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `query` <> '" . $url_type . "=" . $element_id . "' AND `keyword` LIKE '" . $this->db->escape($keyword) . "-%'");
    switch ($url_type) {
      case 'path':
        $path = $this->getPath((int)$element_id);
        $query = $this->query("SELECT `seo_url_id`,`keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `query` <> 'path=" . $this->db->escape($path) . "' AND `keyword` LIKE '" . $this->db->escape($keyword) . "-%'");
        break;

      case 'product_id':
        $query = $this->query("SELECT `seo_url_id`,`keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `query` <> '" . $url_type . "=" . (int)$element_id . "' AND `keyword` LIKE '" . $this->db->escape($keyword) . "-%'");
        break;

      case 'manufacturer_id':
        $query = $this->query("SELECT `seo_url_id`,`keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `query` <> '" . $url_type . "=" . (int)$element_id . "' AND `keyword` LIKE '" . $this->db->escape($keyword) . "-%'");
        break;
    }

    foreach ($query->rows as $row) {
      $keywords[$row['seo_url_id']] = $row['keyword'];
    }
    // Проверим на дубли
    $key = array_search($keyword, $keywords);
    $num = 0;
    while ($key) {
      // Есть дубли
      $this->log("SeoUrl занято: '" . $keyword . "'");
      $num++;
      $keyword = $element_name . "-" . (string)$num;
      $key = array_search($keyword, $keywords);
      if ($num > 200) {
        $this->log("[!] больше 200 дублей!", 2);
        $this->errorLog(2500);
      }
    }

    // Обновляем если только были изменения и существует запись
    if ($old_element['keyword'] != $keyword && $old_element['seo_url_id']) {

      $this->query("UPDATE `" . DB_PREFIX . "seo_url` SET `keyword` = '" . $this->db->escape($keyword) . "' WHERE `seo_url_id` = " . $old_element['seo_url_id']);

    } else {

      switch ($url_type) {
        case 'path':
          $path = $this->getPath((int)$element_id);
          $this->query("INSERT INTO " . DB_PREFIX . "seo_url SET `query` = 'path=" . $this->db->escape($path) . "', keyword = '" . $this->db->escape($keyword) . "'");
          break;

        case 'product_id':
          $this->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET `query` = '" . $url_type . "=" . (int)$element_id . "', `keyword` = '" . $this->db->escape($keyword) . "'");
          break;

        case 'manufacturer_id':
          $this->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET `query` = '" . $url_type . "=" . (int)$element_id . "', `keyword` = '" . $this->db->escape($keyword) . "'");
          break;
      }
    }

  }

  private function getKeywordString($str)
  {
    // Переведем в массив по пробелам
    $s = strip_tags($str); // убираем HTML-теги
    $s = preg_replace("/\s+/", " ", $s); // удаляем повторяющие пробелы
    $s = preg_replace("/\,+/", "", $s); // удаляем повторяющие запятые
    $s = preg_replace("~(&lt;)([^&]+)(&gt;)~isu", "", $s); // удаляем HTML символы
    //$s = preg_replace("![^\w\d\s]*!", "", $s); // очищаем строку от недопустимых символов
    $in_obj = explode(' ', $s);
    $out_obj = array();
    foreach ($in_obj as $s) {
      if (function_exists('mb_strlen')) {
        if (mb_strlen($s) < 3) {
          // пропускаем слова длиной менее 3 символов
          continue;
        }
      }
      $out_obj[] = $s;
    }
    // Удаляем повторяющиеся значения
    $out_obj = array_unique($out_obj);
    $str_out = implode(', ', $out_obj);
    return $str_out;
  }

  function query($sql)
  {
    if ($this->config->get('exchange1c_log_debug_line_view') == 1) {
      list ($di) = debug_backtrace();
      $line = sprintf("%04s", $di["line"]);
    } else {
      $line = '';
    }
    $this->log($sql, 3, $line);
    return $this->db->query($sql);
  }

  public function getPath($category_id)
  {
    return implode('_', array_column($this->getCategoryPath($category_id), 'path_id'));
  }

  public function getCategoryPath($category_id)
  {
    $query = $this->db->query("SELECT category_id, path_id, level FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "' ORDER BY level ASC");
    return $query->rows;
  }

  private function log($message, $level = 1, $line = '')
  {
    if ($level <= $this->config->get('exchange1c_log_level')) {

      if ($this->config->get('exchange1c_log_debug_line_view') == 1) {
        if (!$line) {
          list ($di) = debug_backtrace();
          $line = sprintf("%04s", $di["line"]);
        }
      } else {
        $line = '';
      }

      if (is_array($message) || is_object($message)) {
        $this->log->write($line . "(M):");
        $this->log->write(print_r($message, true));
      } else {
        if (mb_substr($message, 0, 1) == '~') {
          $this->log->write('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
          $this->log->write($line . "(M) " . mb_substr($message, 1));
        } else {
          $this->log->write($line . "(M) " . $message);
        }
      }
    }
  }

  private function errorLog($error_num, $arg1 = '', $arg2 = '', $arg3 = '')
  {
    $this->ERROR = $error_num;
    $message = $this->language->get('error_' . $error_num . '_log');
    if (!$message) {
      $this->language->get('error_' . $error_num);
    }
    if ($message && $this->config->get('exchange1c_log_level') > 0) {
      list ($di) = debug_backtrace();
      $debug = "Строка ошибки: " . sprintf("%04s", $di["line"]) . " - ";
      $this->log->write(sprintf($debug . $message, $arg1, $arg2, $arg3));
    }
  }

  private function getSlug($store_id, $language_id, $query = '')
  {
    $slug = null;
    if (!empty($query)) {
      $result = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url 
            WHERE language_id = '" . (int)$language_id . "'
            AND store_id = '" . (int)$store_id . "'
            AND query LIKE '" . $this->db->escape($query) . "'
         ");
    }
    if ($result->rows > 0) {
      $slug = $result->row;
    }
    return $slug;
  }

  public function uniqueSlug($store_id, $language_id, $keyword, $query = '')
  {
    $sql = "SELECT COUNT(*) as `total` FROM " . DB_PREFIX . "seo_url 
	WHERE keyword = '" . $this->db->escape($keyword) . "'
	AND language_id = '" . (int)$language_id . "'
	AND store_id = '" . (int)$store_id . "'";

    if ($query) {
      $sql .= " AND query NOT LIKE '" . $this->db->escape($query) . "'";
    }

    if (strpos($query, 'manufacturer_id=') === 0) {
      $sql .= " AND query LIKE 'manufacturer_id=%'";
    } else {
      $sql .= " AND query NOT LIKE 'manufacturer_id=%'";
    }

    $result = $this->db->query($sql);

    if ($result->row['total'] > 0) {
      $keyword = $keyword . '-' . $result->row['total'];
      return $this->uniqueSlug($store_id, $language_id, $keyword, $query);
    }

    return $keyword;
  }

  public function clearCards()
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "cards`");
    file_put_contents($this->STATUS_FILE, "completed");
  }

  public function clearAllDisconts()
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_discount`");
    file_put_contents($this->STATUS_FILE, "completed");
  }

  public function clearCategories()
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "category`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "category_description`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "category_filter`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "category_path`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "category_to_store`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query LIKE 'category_id=%'");
    file_put_contents($this->STATUS_FILE, "completed");
  }

  public function clearWarehouses()
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "warehouse`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "warehouse_description`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query LIKE 'warehouse_id=%'");
    file_put_contents($this->STATUS_FILE, "completed");
  }

  public function clearAddressesClient()
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "address` WHERE guid != ''");
  }

  public function clearCustomerAddressPriceGroups()
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_address_price_groups`");
  }

  public function clearProducts()
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query LIKE 'product_id=%'");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_image`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_option`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_option_value`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_option_value_image`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_option_value_description`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_option_value_composition`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_quantity`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_description`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_related`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_related_article`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_special`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_store`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_discount`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "product_attribute`");
пше
    $this->db->query("DELETE FROM `" . DB_PREFIX . "attribute`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "attribute_description`");

    $this->db->query("DELETE FROM `" . DB_PREFIX . "option`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "option_description`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "option_value`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "option_value_description`");

    $this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer_description`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer_discount`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_layout`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_store`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query LIKE 'manufacturer_id=%'");

    file_put_contents($this->STATUS_FILE, "completed\n", LOCK_EX);
  }


  public function deleteProductQuantityByStore($warehouses)
  {
    if (!empty($warehouses)) {
      $warehouse_ids = array_map('intval', $warehouses);
      $this->db->query("DELETE FROM `" . DB_PREFIX . "product_quantity` WHERE warehouse_id NOT IN (" . implode(',', $warehouse_ids) . ")");
    } else {
      $this->db->query("DELETE FROM `" . DB_PREFIX . "product_quantity`");
    }
  }


  public function updateFilter()
  {
    $product_ids = array_column($this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product`")->rows, 'product_id');
    if (empty($product_ids)) return;

    // НЕ видаляємо af_attribute_values, щоб зберегти sort_order
    $this->db->query("DELETE FROM `" . DB_PREFIX . "af_product_attribute`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "af_values`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "af_translit`");

    $language_id = (int)$this->config->get('config_language_id') ?: $this->getLanguageId($this->config->get('config_language'));

    // 1. Завантажуємо всі існуючі значення в пам'ять
    $existing_values = [];
    $query = $this->db->query("SELECT attribute_value_id, attribute_id, language_id, text FROM `" . DB_PREFIX . "af_attribute_values`");
    foreach ($query->rows as $row) {
      $val_text = trim($row['text']);
      $existing_values[$row['attribute_id']][$row['language_id']][mb_strtolower($val_text, 'UTF-8')] = $row['attribute_value_id'];
    }

    $data_to_insert = [];
    $af_values_to_ensure = [];

    // === 2. Обробка атрибутів ===
    $attributes = $this->db->query("
      SELECT pa.product_id, pa.attribute_id, pa.language_id,
             REPLACE(REPLACE(TRIM(pa.text), '\r', ''), '\n', '') AS text
      FROM `" . DB_PREFIX . "product_attribute` pa
      WHERE pa.product_id IN (" . implode(',', array_map('intval', $product_ids)) . ")");

    foreach ($attributes->rows as $row) {
      $texts = explode($this->separator ?: ',', $row['text']);
      foreach ($texts as $text) {
        $text = trim($text);
        if ($text === '') continue;

        $lower_text = mb_strtolower($text, 'UTF-8');

        if (!isset($existing_values[$row['attribute_id']][$row['language_id']][$lower_text])) {
          // Використовуємо INSERT IGNORE про всяк випадок
          $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "af_attribute_values` SET attribute_id = '" . (int)$row['attribute_id'] . "', language_id = '" . (int)$row['language_id'] . "', text = '" . $this->db->escape($text) . "', sort_order = 0");
          $attribute_value_id = $this->db->getLastId();
          
          if (!$attribute_value_id) {
            $tmp_q = $this->db->query("SELECT attribute_value_id FROM `" . DB_PREFIX . "af_attribute_values` WHERE attribute_id = '" . (int)$row['attribute_id'] . "' AND language_id = '" . (int)$row['language_id'] . "' AND text = '" . $this->db->escape($text) . "'");
            $attribute_value_id = $tmp_q->num_rows ? $tmp_q->row['attribute_value_id'] : 0;
          }
          
          if ($attribute_value_id) {
            $existing_values[$row['attribute_id']][$row['language_id']][$lower_text] = $attribute_value_id;
          }
        } else {
          $attribute_value_id = $existing_values[$row['attribute_id']][$row['language_id']][$lower_text];
        }

        if ($attribute_value_id) {
          $af_values_to_ensure['attribute'][$row['attribute_id']][$attribute_value_id] = true;
          $data_to_insert[] = "('{$row['product_id']}', '{$row['attribute_id']}', '{$attribute_value_id}')";
        }
      }
    }

    // === 3. Обробка опцій ===
    $options = $this->db->query("
      SELECT pov.product_id, po.option_id, ovd.name AS value_name, ovd.language_id
      FROM `" . DB_PREFIX . "product_option_value` pov
      JOIN `" . DB_PREFIX . "product_option` po ON po.product_option_id = pov.product_option_id
      JOIN `" . DB_PREFIX . "option_value_description` ovd ON ovd.option_value_id = pov.option_value_id
      WHERE pov.product_id IN (" . implode(',', array_map('intval', $product_ids)) . ")
        AND ovd.language_id = '" . $language_id . "'
    ");

    foreach ($options->rows as $row) {
      $text = trim($row['value_name']);
      if ($text === '') continue;

      $lower_text = mb_strtolower($text, 'UTF-8');

      if (!isset($existing_values[$row['option_id']][$row['language_id']][$lower_text])) {
        $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "af_attribute_values` SET attribute_id = '" . (int)$row['option_id'] . "', language_id = '" . (int)$row['language_id'] . "', text = '" . $this->db->escape($text) . "', sort_order = 0");
        $attribute_value_id = $this->db->getLastId();
        
        if (!$attribute_value_id) {
          $tmp_q = $this->db->query("SELECT attribute_value_id FROM `" . DB_PREFIX . "af_attribute_values` WHERE attribute_id = '" . (int)$row['option_id'] . "' AND language_id = '" . (int)$row['language_id'] . "' AND text = '" . $this->db->escape($text) . "'");
          $attribute_value_id = $tmp_q->num_rows ? $tmp_q->row['attribute_value_id'] : 0;
        }

        if ($attribute_value_id) {
          $existing_values[$row['option_id']][$row['language_id']][$lower_text] = $attribute_value_id;
        }
      } else {
        $attribute_value_id = $existing_values[$row['option_id']][$row['language_id']][$lower_text];
      }

      if ($attribute_value_id) {
        $af_values_to_ensure['option'][$row['option_id']][$attribute_value_id] = true;
        $data_to_insert[] = "('{$row['product_id']}', '{$row['option_id']}', '{$attribute_value_id}')";
      }
    }

    // === 4. Масове вставлення зв'язків ===
    if (!empty($data_to_insert)) {
      $chunks = array_chunk($data_to_insert, 1000);
      foreach ($chunks as $chunk) {
        $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "af_product_attribute` (product_id, attribute_id, attribute_value_id) VALUES " . implode(",", $chunk));
      }
    }

    foreach ($af_values_to_ensure as $type => $groups) {
      foreach ($groups as $group_id => $values) {
        foreach ($values as $value_id => $dummy) {
          $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "af_values` SET `type` = '" . $this->db->escape($type) . "', `group_id` = '" . (int)$group_id . "', `value` = '" . (int)$value_id . "'");
        }
      }
    }

    // === 5. Оновлення поля af_values в таблиці product ===
    $this->db->query("SET SESSION group_concat_max_len = 1000000");
    $this->db->query("
      UPDATE `" . DB_PREFIX . "product` p
      LEFT JOIN (
        SELECT product_id, GROUP_CONCAT(DISTINCT attribute_value_id ORDER BY attribute_value_id ASC SEPARATOR ',') as af_values
        FROM `" . DB_PREFIX . "af_product_attribute`
        GROUP BY product_id
      ) t ON p.product_id = t.product_id
      SET p.af_values = IFNULL(t.af_values, '')
      WHERE p.product_id IN (" . implode(',', array_map('intval', $product_ids)) . ")
    ");

    // === 6. Очистка кешу ===
    foreach ([
               'af-category', 'af-manufacturer', 'af-price', 'af-ean', 'af-filter',
               'af-option', 'af-option-values', 'af-total-attribute', 'af-total-category',
               'af-total-manufacturer', 'af-total-option', 'af-total-stock-status',
               'af-total-filter', 'af-total-rating', 'af-total-ean',
               'af-translit', 'af-url-params'
             ] as $key) {
      $this->deleteCache($key);
    }
    file_put_contents($this->STATUS_FILE, "completed\n", LOCK_EX);
  }


  public function getSetting($code, $store_id = 0)
  {
    $setting_data = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

    foreach ($query->rows as $result) {
      if (!$result['serialized']) {
        $setting_data[$result['key']] = $result['value'];
      } else {
        $setting_data[$result['key']] = json_decode($result['value'], true);
      }
    }
    return $setting_data;
  }

  private function ensureAfValueExists($group_id, $value, $type = 'attribute')
  {
    $query = $this->db->query("SELECT af_value_id FROM `" . DB_PREFIX . "af_values`
        WHERE `type` = '" . $this->db->escape($type) . "'
          AND `group_id` = '" . (int)$group_id . "'
          AND `value` = '" . (int)$value . "'");

    if ($query->num_rows > 0) {
      return (int)$query->row['af_value_id'];
    } else {
      $this->db->query("INSERT INTO `" . DB_PREFIX . "af_values`
            SET `type` = '" . $this->db->escape($type) . "',
                `group_id` = '" . (int)$group_id . "',
                `value` = '" . (int)$value . "'");
      return $this->db->getLastId();
    }
  }

  private function getAttributeValue($text, $attribute_id, $language_id)
  {
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "af_attribute_values`
                            WHERE `text` = '" . $this->db->escape($text) . "'
                              AND `attribute_id` = '" . (int)$attribute_id . "'
                              AND `language_id` = '" . (int)$language_id . "'");

    if ($query->num_rows > 0) {
      return (int)$query->row['attribute_value_id'];
    } else {
      $this->db->query("INSERT INTO `" . DB_PREFIX . "af_attribute_values`
                         SET `attribute_id` = '" . (int)$attribute_id . "',
                             `language_id` = '" . (int)$language_id . "',
                             `text` = '" . $this->db->escape($text) . "'");
      return $this->db->getLastId();
    }
  }

  private function getOptionValue($option_value_id, $option_id, $language_id)
  {
    $query_text = $this->db->query("
    SELECT name FROM `" . DB_PREFIX . "option_value_description`
    WHERE option_value_id = '" . (int)$option_value_id . "'
      AND language_id = '" . (int)$language_id . "'
  ");

    $text = $query_text->num_rows ? $query_text->row['name'] : (string)$option_value_id;

    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "af_attribute_values`
                            WHERE `text` = '" . $this->db->escape($text) . "'
                              AND `attribute_id` = '" . (int)$option_id . "'
                              AND `language_id` = '" . (int)$language_id . "'");

    if ($query->num_rows > 0) {
      return (int)$query->row['attribute_value_id'];
    } else {
      $this->db->query("INSERT INTO `" . DB_PREFIX . "af_attribute_values`
                         SET `attribute_id` = '" . (int)$option_id . "',
                             `language_id` = '" . (int)$language_id . "',
                             `text` = '" . $this->db->escape($text) . "'");
      return $this->db->getLastId();
    }
  }


  private function downloadProductImage($url, $sku)
  {
    if (empty($url) || empty($sku)) return false;

    $filename = $sku . '.jpg';
    $filepath = DIR_IMAGE . 'catalog/product/' . $filename;

    if (!file_exists($filepath)) {
      $ctx = stream_context_create(['http' => ['timeout' => 10]]);
      $content = @file_get_contents($url, false, $ctx);
      if ($content !== false) {
        file_put_contents($filepath, $content);
        return 'catalog/product/' . $filename;
      }
    } else {
      return 'catalog/product/' . $filename;
    }

    return false;
  }

  public function install()
  {
    $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "import` (
			    `id` INT(11) NOT NULL AUTO_INCREMENT
	        PRIMARY KEY (`id`)
		) DEFAULT COLLATE=utf8_general_ci;");

  }

  public function uninstall()
  {
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "import`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'module_ocimport'");
  }
}
