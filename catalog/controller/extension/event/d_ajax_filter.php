<?php
class ControllerExtensionEventDAjaxFilter extends Controller {

    private $codename = 'd_ajax_filter';
    private $route = 'extension/module/d_ajax_filter';
    private $common_setting;

    public function __construct($registry)
    {
        parent::__construct($registry);
        
        $this->load->model($this->route);
        $this->load->model('setting/setting');
        $common_setting = $this->model_setting_setting->getSetting($this->codename);

        if(empty($common_setting[$this->codename.'_setting'])){
            $this->config->load('d_ajax_filter');
            $setting = $this->config->get('d_ajax_filter_setting');

            $common_setting = $setting['general'];
        }
        else{
            $common_setting = $common_setting[$this->codename.'_setting'];
        }
        
        $this->common_setting = $common_setting;
    }

}
