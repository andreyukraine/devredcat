<?php

class ControllerExtensionDAjaxFilterAttribute extends Controller
{
    private $codename = 'd_ajax_filter';
    private $route = 'extension/d_ajax_filter/attribute';
    private $filter_data = array();
    private $attribute_setting = array();

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('extension/module/'.$this->codename);
        $this->load->model($this->route);

        $this->load->language('extension/module/'.$this->codename);

        $this->filter_data = $this->{'model_extension_module_'.$this->codename}->getFitlerData();

        $attribute_setting = $this->config->get($this->codename.'_attributes');

        if(empty($attribute_setting)){
            $this->config->load('d_ajax_filter');
            $setting = $this->config->get('d_ajax_filter_setting');

            $attribute_setting = $setting['attributes'];
        }

        $this->attribute_setting = $attribute_setting;
    }

    public function index($setting) {
        $filters = array();
        $results = $this->{'model_extension_'.$this->codename.'_attribute'}->getAttributes($this->filter_data);

        foreach ($results as $attribute_id => $attribute_info) {
            $attribute_values = $this->{'model_extension_'.$this->codename.'_attribute'}->getAttributeValues($attribute_id, $this->filter_data);

            $attribute_setting = $this->{'model_extension_'.$this->codename.'_attribute'}->getSetting($attribute_id, $this->attribute_setting, $setting['module_setting']);

            $attribute_value_data = array();
            if($attribute_setting['status']){
                
                foreach ($attribute_values as $attribute_value_id => $attribute_value_info) {

                    if(!empty($attribute_value_info['image'])&&file_exists(DIR_IMAGE.$attribute_value_info['image'])) {
                        $thumb = $this->model_tool_image->resize($attribute_value_info['image'],45,45);
                    }
                    else {
                        $thumb = $this->model_tool_image->resize('no_image.png',45,45);
                    }

                    $attribute_value_data['_'.$attribute_value_id] = array(
                        'name' => $attribute_value_info['text'],
                        'value' => $attribute_value_id,
                        'thumb' => $thumb
                        );
                }

                if(!empty($attribute_value_data)){

                  $attribute_value_data = $this->{'model_extension_module_'.$this->codename}->sort_values($attribute_value_data, $attribute_setting['sort_order_values']);

                  $attribute_value_data = $this->{'model_extension_module_' . $this->codename}->addMoreValuesItem($attribute_value_data, 'attribute', $attribute_id);

                  $filters['_'.$attribute_id] = array(
                        'caption' => $attribute_info['name'],
                        'slug' => $attribute_info['slug'],
                        'name' => 'attribute',
                        'group_id' => $attribute_id,
                        'type' =>  $attribute_setting['type'],
                        'collapse' =>  $attribute_setting['collapse'],
                        'values' => $attribute_value_data
                        );
                }
            }
        }

        return $filters;
    }

    public function quantity(){

        $quantity = $this->{'model_extension_'.$this->codename.'_attribute'}->getAttributeCount($this->filter_data);


        if(isset($quantity['attribute'])){
            $attribute_quantity = $quantity['attribute'];
        }
        else{
            $attribute_quantity = array();
        }

        return $attribute_quantity;
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
        $groupIdQuery = $this->db->query("SELECT DISTINCT attribute_id FROM " . DB_PREFIX . "attribute_description 
                                             WHERE slug = '" . $this->db->escape($groupSlug) . "'");

        if ($groupIdQuery->num_rows) {
          $group_id = (int)$groupIdQuery->row['attribute_id'];

          $sql = "SELECT `value` FROM " . DB_PREFIX . "af_translit 
                       WHERE text IN (" . implode(',', $escapedSlugs) . ")
                       AND type = 'attribute' 
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

    public function rewrite($data){
        $result = array();
        if(!empty($data)){
            foreach ($data as $attribute_id => $attribute_values) {
                $attribute_info = $this->{'model_extension_'.$this->codename.'_attribute'}->getAttribute($attribute_id);
                $attribute_info['name'] = html_entity_decode($attribute_info['name'], ENT_QUOTES, 'UTF-8');
                $query = array('a'.$attribute_id.'-'.$this->{'model_extension_module_'.$this->codename}->translit($attribute_info['name']));
                foreach ($attribute_values as $attribute_value_id) {
                    $attribute_value_info = $this->{'model_extension_'.$this->codename.'_attribute'}->getAttributeValue($attribute_value_id);

                    $name = html_entity_decode($attribute_value_info['name'], ENT_QUOTES, 'UTF-8');

                    $query[] = $this->{'model_extension_module_'.$this->codename}->setTranslit($name, 'attribute', $attribute_id, $attribute_value_id);
                }
                if(count($query) > 1){
                    $result[] = implode(',', $query);
                }
            }
            
        }

        return $result;
    }
}
