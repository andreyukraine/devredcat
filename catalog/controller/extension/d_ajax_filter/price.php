<?php

class ControllerExtensionDAjaxFilterPrice extends Controller
{
    private $codename = 'd_ajax_filter';
    private $route = 'extension/d_ajax_filter/price';
    private $filter_data = array();
    

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('extension/module/'.$this->codename);
        $this->load->model($this->route);
        $this->load->language('extension/module/'.$this->codename);

        $this->filter_data = $this->{'model_extension_module_'.$this->codename}->getFitlerData();
        

    }

    public function index($setting){
        $filters = array();
//        $result = $this->{'model_extension_'.$this->codename.'_price'}->getPriceForCategory($this->filter_data);
//
//        if($result['min'] != '' && $result['max'] != ''){
//            $min = floor($this->currency->format($result['min'], $this->session->data['currency'], '', false));
//            $max = ceil($this->currency->format($result['max'], $this->session->data['currency'], '', false));
//
//            $filters['_0'] = array(
//                'caption' => $this->language->get('text_price'),
//                'name' => 'price',
//                'group_id' => 0,
//                'type' => $setting['type'],
//                'mode' => 'range',
//                'values' => array($min, $max),
//                'collapse'=> $setting['collapse']
//                );
//        }

        return $filters;
    }

    public function quantity(){

        $result = array();

//        $price_data = $this->{'model_extension_'.$this->codename.'_price'}->getPriceForCategory($this->filter_data);
//
//        $price_min = !empty($price_data['min'])?$price_data['min']:0;
//        $price_max = !empty($price_data['max'])?$price_data['max']:0;
//
//        $min = floor($this->currency->format($price_min, $this->session->data['currency'], '', false));
//        $max = ceil($this->currency->format($price_max, $this->session->data['currency'], '', false));
//
//        $result = array(
//            '0' => $min,
//            '1' => $max
//            );

        return array($result);

    }

    public function url($query){
        $groups = array();

        preg_match('/price,([0-9-]+)\\/?/', $query, $matches);
        if(!empty($matches[1])){
            $groups[] = explode('-', $matches[1]);
        }

        return $groups;
    }

    public function rewrite($data){
        $result = array();

        if(!empty($data[0])){
            $result[] = 'price,'.$data[0][0].'-'.$data[0][1];
        }

        return $result;
    }
}
