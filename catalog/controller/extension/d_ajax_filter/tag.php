<?php

class ControllerExtensionDAjaxFilterTag extends Controller
{
    private $codename = 'd_ajax_filter';
    private $route = 'extension/d_ajax_filter/tag';
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
        $results = $this->{'model_extension_'.$this->codename.'_tag'}->getTags($this->filter_data);

        $tag_data = array();

        foreach ($results as $tag_id => $value) {

            $tag_data['_'.$tag_id] = array(
                'name' => $value['name'],
                'value' => $tag_id
                );

        }
        if(!empty($tag_data)){

            $tag_data = $this->{'model_extension_module_'.$this->codename}->sort_values($tag_data, $setting['sort_order_values']);

            $filters['_0'] = array(
                'caption' => $this->language->get('text_tag'),
                'name' => 'tag',
                'group_id' => 0,
                'type' =>  $setting['type'],
                'collapse' =>  $setting['collapse'],
                'values' => $tag_data,
                'sort_order'=> $setting['sort_order']
                );
        }
        return $filters;
    }

    public function selected($setting){
        $selected = array();
        if(isset($this->selected_params['tag'][0]))
        {
            foreach ($this->selected_params['tag'][0] as $tag_id) {
                $tag_info = $this->{'model_extension_'.$this->codename.'_tag'}->getTag($tag_id);
                $selected[] = array(
                    'name' => $tag_info['name'],
                    'type' => 'tag_0',
                    'mode' => $setting['type'],
                    'caption' => $this->language->get('text_tag'),
                    'value' => $tag_id
                    );
            }
        }
        return $selected;
    }

    public function quantity(){

        $quantity = $this->{'model_extension_'.$this->codename.'_tag'}->getTotalTag($this->filter_data, true);

        if(isset($quantity['tag'])){
            $tag_quantity = $quantity['tag'];
        }
        else{
            $tag_quantity = array();
        }

        return $tag_quantity;
    }

    public function url($query){
        $groups = array();

        preg_match('/tag,([^&><]*)\\/?/', $query, $matches);
        if(!empty($matches[1])){
            
            $names = explode(',', $matches[1]);
            $names =array_map(function($val){ return "'".$val."'"; }, $names);
            $results = $this->{'model_extension_module_'.$this->codename}->getTranslit($names, 'tag', 0);
            if(!empty($results)){
                $groups[] = $results;
            }
        }

        return $groups;
    }

    public function rewrite($data){
        $result = array();
        if(!empty($data[0])){
            $query = array('tag');
            foreach ($data[0] as $tag_id) {
                $tag_info = $this->{'model_extension_'.$this->codename.'_tag'}->getTag($tag_id);

                $name = html_entity_decode($tag_info['value'], ENT_QUOTES, 'UTF-8');
                $query[] = $this->{'model_extension_module_'.$this->codename}->setTranslit($name, 'tag', 0, $tag_id);
            }

            if(count($query) > 1){
                $result[] = implode(',', $query);
            }
        }

        return $result;
    }
}
