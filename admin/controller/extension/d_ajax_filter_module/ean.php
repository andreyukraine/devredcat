<?php

class ControllerExtensionDAjaxFilterModuleEan extends Controller
{
    private $codename = 'd_ajax_filter';
    private $route = 'extension/d_ajax_filter_module/ean';
    
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model($this->route);
    }
    public function updateProduct($product_id){
        $new_values = $this->{'model_extension_'.$this->codename.'_module_ean'}->updateProduct($product_id);
        return $new_values;
    }

    public function step($data){
        $count = $this->{'model_extension_'.$this->codename.'_module_ean'}->step($data);
        return $count;
    }

    public function prepare(){
        $this->{'model_extension_'.$this->codename.'_module_ean'}->prepare();
    }

    public function install(){
        $this->{'model_extension_'.$this->codename.'_module_ean'}->installModule();
    }

    public function uninstall(){
        $this->{'model_extension_'.$this->codename.'_module_ean'}->uninstallModule();
    }
}