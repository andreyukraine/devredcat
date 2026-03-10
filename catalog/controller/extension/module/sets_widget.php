<?php

class ControllerExtensionModuleSetsWidget extends Controller {

    public function index($setting) {

        if (!$setting['status'])
            return;

        $this->load->language('extension/module/set');
        $this->load->model('catalog/product');

        $data['text_sets'] = $this->language->get('text_sets');
        $data['text_buy_sets'] = $this->language->get('text_buy_sets');
        $data['text_economy'] = $this->language->get('text_economy');

        if (isset($setting['product']))
            $all_sets = array();

        foreach ($setting['product'] as $pr) {
            $sets = $this->load->controller('extension/module/sets/getSet', $pr['id']);
            if ($sets)
                $all_sets[] = $sets;
        }
        $data['sets_array'] = $all_sets;

        if (!$data['sets_array'])
            return;

        $this->document->addScript('catalog/view/javascript/sets/script.js');

        $this->document->addScript('catalog/view/javascript/slick/slick.min.js');
        $this->document->addStyle('catalog/view/javascript/slick/slick.css');
        $this->document->addStyle('catalog/view/javascript/slick/slick-theme.css');

        $this->document->addStyle('catalog/view/javascript/sets/style.css');
        
        $data['decimal_place'] = $this->currency->getDecimalPlace($this->session->data['currency']);
        $data['show_disc_prec'] = $this->config->get('module_sets_show_disc_prec');
        $data['show_old_price'] = $this->config->get('module_sets_show_old_price');
        $data['show_qty'] = $this->config->get('module_sets_show_qty');

        $data['selector'] = $this->config->get('module_sets_selector');
        $data['position'] = $this->config->get('module_sets_position');

        if (floatval(VERSION) >= 2.2)
            return $this->load->view('extension/module/sets_widget', $data);
        else
            return $this->load->view('default/template/extension/module/sets_widget.tpl', $data);
    }

}
