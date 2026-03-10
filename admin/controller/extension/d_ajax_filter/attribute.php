<?php

/*
*  location: admin/controller
*/

class ControllerExtensionDAjaxFilterAttribute extends Controller
{
    private $codename = 'd_ajax_filter';
    private $route = 'extension/d_ajax_filter/attribute';
    private $extension = array();
    private $config_file = '';
    private $store_id = 0;
    private $error = array();
    
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model($this->route);
        $this->load->model('extension/module/'.$this->codename);
        $this->load->language($this->route);
        
        //extension.json
        $this->extension = json_decode(file_get_contents(DIR_SYSTEM.'library/d_shopunity/extension/'.$this->codename.'.json'), true);
        $this->d_shopunity = (file_exists(DIR_SYSTEM.'library/d_shopunity/extension/d_shopunity.json'));
        $this->d_admin_style = (file_exists(DIR_SYSTEM.'library/d_shopunity/extension/d_admin_style.json'));
        
        //Store_id (for multistore)
        if (isset($this->request->get['store_id'])) { 
            $this->store_id = $this->request->get['store_id'];
        }
    }
    public function index()
    {
        $this->load->model('extension/d_opencart_patch/url');
        $this->load->model('extension/d_opencart_patch/load');
        $this->load->model('extension/d_opencart_patch/user');

        $this->document->addStyle('view/stylesheet/shopunity/bootstrap.css');

        $this->document->addScript('view/javascript/shopunity/bootstrap-switch/bootstrap-switch.min.js');
        $this->document->addStyle('view/stylesheet/shopunity/bootstrap-switch/bootstrap-switch.css');

        $this->document->addScript('view/javascript/shopunity/rubaxa-sortable/sortable.js');
        $this->document->addStyle('view/stylesheet/shopunity/rubaxa-sortable/sortable.css');

        $this->document->addScript('view/javascript/d_ajax_filter/library/tinysort.js');

        $this->document->addStyle('view/stylesheet/d_ajax_filter/attribute.css');

        $this->document->addScript('view/javascript/d_ajax_filter/library/jquery.serializejson.js');
        $this->document->addScript('view/javascript/d_ajax_filter/library/underscore-min.js');
        $this->document->addScript('view/javascript/d_ajax_filter/attribute.js');

        if($this->d_admin_style){
            $this->load->model('extension/d_admin_style/style');

            $this->model_extension_d_admin_style_style->getAdminStyle('light');
        }

        // Add more styles, links or scripts to the project is necessary
        $url_params = array();
        $url = '';

        if (isset($this->response->get['store_id'])) {
            $url_params['store_id'] = $this->store_id;
        }

        if (isset($this->response->get['config'])) {
            $url_params['config'] = $this->response->get['config'];
        }

        $url = ((!empty($url_params)) ? '&' : '') . http_build_query($url_params);

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->model_extension_d_opencart_patch_url->link('common/home')
            );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->model_extension_d_opencart_patch_url->link('marketplace/extension', 'type=module')
            );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->model_extension_d_opencart_patch_url->link($this->route, $url)
            );

        if(isset($this->session->data['success'])){
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }


        $this->document->setTitle($this->language->get('heading_title_main'));
        $data['heading_title'] = $this->language->get('heading_title_main');
        $data['text_form'] = $this->language->get('text_form');

        $data['text_setting'] = $this->language->get('text_setting');
        $data['text_layout'] = $this->language->get('text_layout');
        $data['text_attributes'] = $this->language->get('text_attributes');
        $data['text_options'] = $this->language->get('text_options');
        $data['text_filters'] = $this->language->get('text_filters');
        $data['text_configuration'] = $this->language->get('text_configuration');
        $data['text_warning_select_attribute'] = $this->language->get('text_warning_select_attribute');
        $data['text_important'] = $this->language->get('text_important');
        $data['text_warning_attribute_individual'] = $this->language->get('text_warning_attribute_individual');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_none'] = $this->language->get('text_none');
        $data['text_file_manager'] = $this->language->get('text_file_manager');
        $data['text_warning_default_setting'] = $this->language->get('text_warning_default_setting');
        $data['text_warning_sort_order_value'] = $this->language->get('text_warning_sort_order_value');
        $data['text_warning_image_value'] = $this->language->get('text_warning_image_value');
        $data['text_general_attribute_setting'] = $this->language->get('text_general_attribute_setting');
        $data['text_individual_setting'] = $this->language->get('text_individual_setting');
        $data['text_default_setting'] = $this->language->get('text_default_setting');
        $data['text_on'] = $this->language->get('text_on');
        $data['text_off'] = $this->language->get('text_off');
        $data['text_attribute_default_general'] = $this->language->get('text_attribute_default_general');


        $data['text_default'] = $this->language->get('text_default');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');

        $data['text_list'] = $this->language->get('text_list');
        $data['text_sort_values'] = $this->language->get('text_sort_values');
        $data['text_image'] = $this->language->get('text_image');
        $data['text_attribute_default'] = $this->language->get('text_attribute_default');
        $data['text_individual_attribute_setting'] = $this->language->get('text_individual_attribute_setting');

        $data['entry_attribute'] = $this->language->get('entry_attribute');
        $data['entry_additional_image'] = $this->language->get('entry_additional_image');
        $data['entry_attribute_value'] = $this->language->get('entry_attribute_value');
        $data['entry_type'] = $this->language->get('entry_type');
        $data['entry_sort_order_values'] = $this->language->get('entry_sort_order_values');
        $data['entry_collapse'] = $this->language->get('entry_collapse');

        $data['column_status'] = $this->language->get('column_status');
        $data['column_type'] = $this->language->get('column_type');
        $data['column_collapse'] = $this->language->get('column_collapse');
        $data['column_sort_order_values'] = $this->language->get('column_sort_order_values');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_reset_sort_order'] = $this->language->get('button_reset_sort_order');
        $data['button_reset_image'] = $this->language->get('button_reset_image');

        $data['tabs'] = $this->{'model_extension_module_'.$this->codename}->getTabs('attribute');

        $url = '';

        if(isset($this->request->get['module_id'])){
            $url .= '&module_id='.$this->request->get['module_id'];
        }

        $data['action'] = $this->model_extension_d_opencart_patch_url->link('extension/'.$this->codename.'/attribute/save', $url);

        $data['cancel'] = $this->model_extension_d_opencart_patch_url->link('marketplace/extension', 'type=module');

        // Variable
        $data['codename'] = $this->codename;
        $data['route'] = $this->route;
        $data['store_id'] = $this->store_id;
        $data['extension'] = $this->extension;
        $data['config'] = $this->config_file;

        $data['version'] = $this->extension['version'];
        $data['token'] = $this->model_extension_d_opencart_patch_user->getToken();
        $data['token_url'] = $this->model_extension_d_opencart_patch_user->getUrlToken();

        $this->load->model('setting/setting');

        $setting = $this->model_setting_setting->getSetting($this->codename.'_attributes');

        if(!empty($setting[$this->codename.'_attributes'])){
            $data['setting'] = $setting[$this->codename.'_attributes'];
        }
        else{
            $this->config->load('d_ajax_filter');
            $setting = $this->config->get('d_ajax_filter_setting');

            $data['setting'] = $setting['attributes'];
        }

        if(!empty($data['setting']['attributes'])){
            $this->load->model('catalog/attribute');
            foreach ($data['setting']['attributes'] as $attribute_id => $value) {
                $attribute_info = $this->model_catalog_attribute->getAttribute($attribute_id);
                $data['setting']['attributes'][$attribute_id]['name'] = strip_tags(html_entity_decode($attribute_info['name'], ENT_QUOTES, 'UTF-8'));
            }
        }

        $this->load->model('localisation/language');

        $data['languages'] = $this->model_localisation_language->getLanguages();

        $this->load->model('catalog/attribute_group');

        $data['attribute_groups'] = $this->{'model_extension_'.$this->codename.'_attribute'}->getAttributeGroups($this->config->get('config_language_id'));

        $data['base_types'] = array(
            'radio' => $this->language->get('text_base_type_radio'),
            'select' => $this->language->get('text_base_type_select'),
            'checkbox' => $this->language->get('text_base_type_checkbox'),
            'radio_and_image' => $this->language->get('text_base_type_radio_and_image'),
            'checkbox_and_image' => $this->language->get('text_base_type_checkbox_and_image'),
            'image_radio' => $this->language->get('text_base_type_image_radio'),
            'image_checkbox' => $this->language->get('text_base_type_image_checkbox')
            );

        $data['sort_order_types'] = array(
            'default' => $this->language->get('text_sort_order_type_default'),
            'string_asc' => $this->language->get('text_sort_order_type_string_asc'),
            'string_desc' => $this->language->get('text_sort_order_type_string_desc'),
            'numeric_asc' => $this->language->get('text_sort_order_type_numeric_asc'),
            'numeric_desc' => $this->language->get('text_sort_order_type_numeric_desc'),
            );

        $this->load->model('tool/image');

        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->model_extension_d_opencart_patch_load->view($this->route, $data));
    }

    public function save(){
        $json = array();

        $this->load->model('extension/d_opencart_patch/url');
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting($this->codename.'_attributes', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if(isset($this->request->get['module_id'])){
                $url .= '&module_id='.$this->request->get['module_id'];
            }

            $json['redirect'] = str_replace('&amp;','&',$this->model_extension_d_opencart_patch_url->link($this->route, $url));
            $json['success'] = 'success';
        }
        else{
            $json['errors'] = $this->error;
            $json['error'] = $this->error['warning'];

        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        
    }

    public function getAttributeGroups(){
        $json = array();
        if(isset($this->request->post['language_id'])){
            $language_id = $this->request->post['language_id'];
        }
        if(isset($language_id))
        {
            $this->load->model('tool/image');

            $json['values'] = array();

            $results = $this->{'model_extension_'.$this->codename.'_attribute'}->getAttributeGroups($language_id);

            foreach ($results as $attribute_group) {
                $json['values'][] = array(
                    'id' => $attribute_group['attribute_group_id'],
                    'name' => strip_tags(html_entity_decode($attribute_group['name'], ENT_QUOTES, 'UTF-8'))
                    );
            }
            $json['success'] = 'success';
        }
        else{
            $json['error'] = 'error';
        }
        $this->response->setOutput(json_encode($json));
    }

    public function getAttributes(){
        $json = array();
        if(isset($this->request->post['attribute_group_id'])){
            $attribute_group_id = $this->request->post['attribute_group_id'];
        }
        if(isset($this->request->post['language_id'])){
            $language_id = $this->request->post['language_id'];
        }
        if(isset($attribute_group_id) && isset($language_id))
        {
            $this->load->model('tool/image');

            $filter_data = array(
                'filter_attribute_group_id' => $attribute_group_id
                );
            $json['values'] = array();

            $results = $this->{'model_extension_'.$this->codename.'_attribute'}->getAttributesByAttributeGroup($attribute_group_id, $language_id);

            foreach ($results as $attribute) {
                $json['values'][] = array(
                    'id' => $attribute['attribute_id'],
                    'name' => strip_tags(html_entity_decode($attribute['name'], ENT_QUOTES, 'UTF-8'))
                    );
            }
            $json['success'] = 'success';
        }
        else{
            $json['error'] = 'error';
        }
        $this->response->setOutput(json_encode($json));
    }

    public function getAttributeValues()
    {
        $json = array();

        if(isset($this->request->post['attribute_id'])){
            $attribute_id = $this->request->post['attribute_id'];
        }

        if(isset($this->request->post['language_id'])){
            $language_id = $this->request->post['language_id'];
        }
        
        if(isset($attribute_id))
        {   
            $json['values'] = array();
            $results = $this->{'model_extension_'.$this->codename.'_attribute'}->getAttributeValues($attribute_id, $language_id);

            foreach ($results as $attribute_value) {
                $json['values'][] =  array(
                    'attribute_value_id' => $attribute_value['attribute_value_id'],
                    'text' => $attribute_value['text'],
                    'sort_order' =>  $attribute_value['sort_order']
                    );
            }
            $json['success'] = 'success';
        }
        else{
            $json['error'] = 'error';
        }
        $this->response->setOutput(json_encode($json));
    }

    public function getAttributeImages(){
        if(isset($this->request->post['attribute_id'])){
            $attribute_id = $this->request->post['attribute_id'];
        }

        if(isset($this->request->post['language_id'])){
            $language_id = $this->request->post['language_id'];
        }

        if(isset($attribute_id)&&isset($language_id))
        {

            $this->load->model('tool/image');

            $results = $this->{'model_extension_'.$this->codename.'_attribute'}->getAttributeValues($attribute_id, $language_id);
            $json['values'] = array();
            foreach ($results as $key => $attribute_value) {

                if(!empty($attribute_value['image']))
                {
                    $thumb = $this->model_tool_image->resize($attribute_value['image'],100,100);
                }
                else {
                    $thumb = $this->model_tool_image->resize('no_image.png',100,100);
                }

                $json['values'][] =  array(
                    'attribute_value_id' => $attribute_value['attribute_value_id'],
                    'image' => $attribute_value['image'],
                    'text' => $attribute_value['text'],
                    'thumb' => $thumb
                    );
            }
            $json['success'] = 'success';
        }
        else{
            $json['error'] = 'error';
        }

        $this->response->setOutput(json_encode($json));
    }

    public function editAttributeValues()
    {
        $json = array();
        if(!empty($this->request->post['attribute_values']))
        {
            $this->{'model_extension_'.$this->codename.'_attribute'}->editAttributeValues($this->request->post['attribute_values']);
            $json['success'] = 'success';
        }
        else {
            $json['error'] = 'error';
        }
        $this->response->setOutput(json_encode($json));
    }

    public function editAttributeImages()
    {
        $json = array();
        if(!empty($this->request->post['attribute_images']))
        {
            $this->{'model_extension_'.$this->codename.'_attribute'}->editAttributeImages($this->request->post['attribute_images']);
            $json['success'] = 'success';
        }
        else {
            $json['error'] = 'error';
        }
        $this->response->setOutput(json_encode($json));
    }

    private function validate($permission = 'modify')
    {

        if (!$this->user->hasPermission($permission, $this->route)) {
            $this->error['warning'] = $this->language->get('error_permission');
            return false;
        }

        return true;
    }

    public function autocomplete() {
        $json = array();

        if (isset($this->request->get['filter_name'])) {

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'start'       => 0,
                'limit'       => 10
                );

            $results = $this->{'model_extension_'.$this->codename.'_attribute'}->getAttributes($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'attribute_id'    => $result['attribute_id'],
                    'name'            => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'attribute_group' => $result['attribute_group']
                    );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}