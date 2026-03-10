<?php

class ControllerExtensionDAjaxFilterOption extends Controller
{
  private $codename = 'd_ajax_filter';
  private $route = 'extension/d_ajax_filter/option';
  private $filter_data = array();

  private $option_setting = array();

  public function __construct($registry)
  {
    parent::__construct($registry);
    $this->load->model('extension/module/' . $this->codename);
    $this->load->model($this->route);

    $this->load->language('extension/module/' . $this->codename);

    $this->filter_data = $this->{'model_extension_module_' . $this->codename}->getFitlerData();


    $option_setting = $this->config->get($this->codename . '_options');

    if (empty($option_setting)) {
      $this->config->load('d_ajax_filter');
      $setting = $this->config->get('d_ajax_filter_setting');

      $option_setting = $setting['options'];
    }

    $this->option_setting = $option_setting;
  }

  public function index($setting)
  {
    $filters = array();
    $results = $this->{'model_extension_' . $this->codename . '_option'}->getOptions($this->filter_data);

    foreach ($results as $option_id => $option_info) {
      $option_values = $this->{'model_extension_' . $this->codename . '_option'}->getOptionValues($option_id);

      $option_setting = $this->{'model_extension_' . $this->codename . '_option'}->getSetting($option_id, $this->option_setting, $setting['module_setting']);

      $option_value_data = array();
      if ($option_setting['status']) {

        // У методі index():
        foreach ($option_values as $option_value_id => $option_value_info) {
          // Додано перевірку наявності ключа 'name'
          $name = isset($option_value_info['text'])
            ? html_entity_decode($option_value_info['text'], ENT_QUOTES, 'UTF-8')
            : 'Option ' . $option_value_id;

          $thumb = 'no_image.png';
          if (!empty($option_value_info['image']) && file_exists(DIR_IMAGE . $option_value_info['image'])) {
            $thumb = $this->model_tool_image->resize($option_value_info['image'], 45, 45);
          }

          $option_value_data['_' . $option_value_id] = array(
            'name' => $name,
            'value' => $option_value_id,
            'thumb' => $thumb
          );
        }

        if (!empty($option_value_data)) {

          $option_value_data = $this->{'model_extension_module_' . $this->codename}->sort_values($option_value_data, "string_asc");

          $option_value_data = $this->{'model_extension_module_' . $this->codename}->addMoreValuesItem($option_value_data, 'option', $option_id);

          $filters['_' . $option_id] = array(
            'caption' => html_entity_decode($option_info['name'], ENT_QUOTES, 'UTF-8'),
            'slug' => $option_info['slug'],
            'name' => 'option',
            'group_id' => $option_id,
            'type' => $option_setting['type'],
            'collapse' => $option_setting['collapse'],
            'values' => $option_value_data,
            'sort_order' => $setting['sort_order']
          );
        }
      }
    }

    return $filters;
  }

  public function quantity()
  {

    $quantity = $this->{'model_extension_' . $this->codename . '_option'}->getOptionCount($this->filter_data);


    if (isset($quantity['option'])) {
      $option_quantity = $quantity['option'];
    } else {
      $option_quantity = array();
    }

    return $option_quantity;
  }

  public function url($query)
  {
    $options = [];

    $segments = explode('/', $query);

    foreach ($segments as $segment) {
      if (strpos($segment, '=') !== false && strpos($segment, 'manufacturer=') === false) {
        list($groupSlug, $valueList) = explode('=', $segment, 2);
        $slugs = explode(',', $valueList);

        // Екрануємо значення і додаємо лапки
        $escapedSlugs = array_map(function ($slug) {
          return "'" . $this->db->escape($slug) . "'";
        }, $slugs);

        // Отримуємо group_id для цієї опції
        $groupIdQuery = $this->db->query("SELECT DISTINCT option_id FROM " . DB_PREFIX . "option_description 
                                             WHERE slug = '" . $this->db->escape($groupSlug) . "'");

        if ($groupIdQuery->num_rows) {
          $group_id = (int)$groupIdQuery->row['option_id'];

          $sql = "SELECT `value` FROM " . DB_PREFIX . "af_translit 
                       WHERE text IN (" . implode(',', $escapedSlugs) . ")
                       AND type = 'option' 
                       AND group_id = " . $group_id;

          $results = $this->db->query($sql);
          if ($results->num_rows) {
            foreach ($results->rows as $row) {
              $options[$group_id][] = $row['value'];
            }
          }
        }
      }
    }

    return $options;
  }

  public function rewrite($data)
  {
    $result = array();
    if (!empty($data)) {
      foreach ($data as $option_id => $option_values) {
        $option_info = $this->{'model_extension_' . $this->codename . '_option'}->getOption($option_id);
        $opt_name = $option_id; // Значення за замовчуванням

        if ($option_info && isset($option_info['name'])) {
          $opt_name = html_entity_decode($option_info['name'], ENT_QUOTES, 'UTF-8');
        }

        $query = array('o' . $option_id . '-' . $this->{'model_extension_module_' . $this->codename}->translit($opt_name));

        foreach ($option_values as $option_value_id) {
          $option_value_info = $this->{'model_extension_' . $this->codename . '_option'}->getOptionValue($option_value_id);
          $value_name = $opt_name . "-" . $option_value_id; // Значення за замовчуванням

          if ($option_value_info && isset($option_value_info['name'])) {
            $value_name = html_entity_decode($option_value_info['name'], ENT_QUOTES, 'UTF-8');
          }

          $query[] = $this->{'model_extension_module_' . $this->codename}->setTranslit($value_name, 'option', $option_id, $option_value_id);
        }

        if (count($query) > 1) {
          $result[] = implode(',', $query);
        }
      }

    }

    return $result;
  }

}
