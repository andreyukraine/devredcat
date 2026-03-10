<?php

class ControllerExtensionDAjaxFilterCategory extends Controller
{
    private $codename = 'd_ajax_filter';
    private $route = 'extension/d_ajax_filter/category';
    private $filter_data = array();
    

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('extension/module/'.$this->codename);
        $this->load->model($this->route);
        $this->load->language('extension/module/'.$this->codename);
        $this->filter_data = $this->{'model_extension_module_'.$this->codename}->getFitlerData();
    }

    public function index($setting) {
        $filters = array();
        $results = $this->{'model_extension_'.$this->codename.'_category'}->getCategories($this->filter_data);

        $category_data = array();

        foreach ($results as $category_id => $value) {

            if(!empty($value['image'])&&file_exists(DIR_IMAGE.$value['image'])) {
                $thumb = $this->model_tool_image->resize($value['image'],45,45);
            }
            else {
                $thumb = $this->model_tool_image->resize('no_image.png',45,45);
            }

            $category_data['_'.$category_id] = array(
                'name' => html_entity_decode($value['name'], ENT_QUOTES, 'UTF-8'),
                'value' => $category_id,
                'thumb' => $thumb,
                );

        }
        if(!empty($category_data)){

            $category_data = $this->{'model_extension_module_'.$this->codename}->sort_values($category_data, $setting['sort_order_values']);

            $filters['_0'] = array(
                'caption' => $this->language->get('text_category'),
                'name' => 'category',
                'group_id' => '0',
                'type' =>  $setting['type'],
                'collapse' =>  $setting['collapse'],
                'values' => $category_data,
                );
        }
        
        return $filters;
    }

    public function quantity(){

        $quantity = $this->{'model_extension_'.$this->codename.'_category'}->getTotalCategory($this->filter_data, true);

        if(isset($quantity['category'])){
            $category_quantity = $quantity['category'];
        }
        else{
            $category_quantity = array();
        }

        return $category_quantity;
    }

    public function url($query){
        $groups = array();

        preg_match('/category,([a-zA-Z\\s,-]+)\\/?/', $query, $matches);

        if(!empty($matches[1])){
            
            $names = explode(',', $matches[1]);
            $names =array_map(function($val){ return "'".$val."'"; }, $names);
            $results = $this->{'model_extension_module_'.$this->codename}->getTranslit($names, 'category', 0);
            if(!empty($results)){
                $groups[] = $results;
            }
        }

        return $groups;
    }

    public function rewrite($data){
        $result = array();
        if(!empty($data[0])){
            $query = array('category');
            foreach ($data[0] as $category_id) {
                $category_info = $this->{'model_extension_'.$this->codename.'_category'}->getCategory($category_id);
                $name = html_entity_decode($category_info['name'], ENT_QUOTES, 'UTF-8');
                $query[] = $this->{'model_extension_module_'.$this->codename}->setTranslit($name, 'category', 0, $category_id);
            }

            if(count($query) > 1){
                $result[] = implode(',', $query);
            }
        }

        return $result;
    }
}
