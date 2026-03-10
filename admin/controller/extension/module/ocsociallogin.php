<?php

class ControllerExtensionModuleOcsociallogin extends Controller
{

    private $error = array();
    private $session_token_key = 'token';
    private $session_token = '';
    private $module_path = '';

    public function __construct($registry)
    {
        parent::__construct($registry);

        if (VERSION >= 3.0) {
            $this->session_token_key = 'user_token';
            $this->session_token = $this->session->data['user_token'];
            $this->extension_path = 'marketplace/extension';
            $this->module_path = 'extension/module';
        } else {
            $this->session_token_key = 'token';
            $this->session_token = $this->session->data['token'];
        }
        if (VERSION <= '2.2.0.0') {
            $this->extension_path = 'extension/module';
            $this->module_path = 'module';
        } else if (VERSION < 3.0) {
            $this->extension_path = 'extension/extension';
            $this->module_path = 'extension/module';
        }
    }

    public function index()
    {
        $this->load->language($this->module_path . '/ocsociallogin');
        $this->document->setTitle($this->language->get('heading_title_main'));
        $store_id = 0;
        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        }

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            $enable_status['module_ocsociallogin_status'] = $this->request->post['ocsociallogin_status'];
            $this->model_setting_setting->editSetting('module_ocsociallogin', $enable_status, $store_id);
            $this->model_setting_setting->editSetting('ocsociallogin', $this->request->post, $store_id);
            $data['success'] = $this->language->get('text_success');
        }

        $ocsociallogin = $this->model_setting_setting->getSetting('ocsociallogin', $store_id);
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }
        $data['text_edit'] = $this->language->get('text_edit');
        $data['heading_title'] = $this->language->get('heading_title');
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('tab_general_setting'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );
        $data['tab_general_setting'] = $this->language->get('tab_general_setting');
        $data['cancel'] = $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token . '&type=module', true);
        $data['action'] = $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
        $data['text_enabled'] = $this->language->get('text_enabled');

        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['text_module_name'] = $this->language->get('text_module_name');
        $data['text_layout'] = $this->language->get('text_layout');
        $data['text_sortorder'] = $this->language->get('text_sortorder');
        $data['text_title'] = $this->language->get('text_title');
        $data['text_button_size'] = $this->language->get('text_button_size');
        $data['text_small'] = $this->language->get('text_small');
        $data['text_large'] = $this->language->get('text_large');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_setting'] = $this->language->get('button_setting');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['setting'] = $this->url->link($this->module_path . '/ocsociallogin/setting', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
        $data['error_empty_field'] = $this->language->get('error_empty_field');
        $data['text_include_font_awesome'] = $this->language->get('text_include_font_awesome');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');



        if (isset($this->request->post['ocsociallogin_status'])) {
            $data['ocsociallogin_status'] = $this->request->post['ocsociallogin_status'];
        } elseif (!empty($ocsociallogin['ocsociallogin_status'])) {
            $data['ocsociallogin_status'] = $ocsociallogin['ocsociallogin_status'];
        } else {
            $data['ocsociallogin_status'] = '0';
        }

        if (isset($this->request->post['ocsociallogin_include_font_awesome'])) {
            $data['ocsociallogin_include_font_awesome'] = $this->request->post['ocsociallogin_include_font_awesome'];
        } elseif (!empty($ocsociallogin['ocsociallogin_include_font_awesome'])) {
            $data['ocsociallogin_include_font_awesome'] = $ocsociallogin['ocsociallogin_include_font_awesome'];
        } else {
            $data['ocsociallogin_include_font_awesome'] = '1';
        }


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $active_tab['active'] = 1;
        $data['tab_common'] = $this->load->controller($this->module_path . '/ocsociallogin/tabs', $active_tab);

        $data_swticher['current_url'] = $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token, true);
        $data_swticher['store_id'] = $store_id;
        $data['storeSwticher'] = $this->load->controller($this->module_path . '/ocsociallogin/storeSwticher', $data_swticher);


        if (VERSION < '2.2.0') {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/ocsociallogin.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/ocsociallogin', $data));
        }
    }

    public function configuration_accounts()
    {
        $this->load->language($this->module_path . '/ocsociallogin');
        $this->document->setTitle($this->language->get('heading_title_main'));
        $store_id = 0;
        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        }

        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->model_setting_setting->editSetting('ocsociallogin_setting', $this->request->post, $store_id);
            $this->session->data['success'] = $this->language->get('text_success_setting');
        }

        $ocsociallogin_setting = $this->model_setting_setting->getSetting('ocsociallogin_setting', $store_id);
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $data['text_edit'] = $this->language->get('tab_accounts_settings');
        $data['heading_title'] = $this->language->get('heading_title');
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('tab_accounts_settings'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin/configuration_accounts', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );
        $data['tab_configuration_accounts'] = $this->language->get('tab_configuration_accounts');
        $data['text_general_setting'] = $this->language->get('text_general_setting');
        $data['text_facebook'] = $this->language->get('text_facebook');
        $data['text_google'] = $this->language->get('text_google');
        $data['text_linkedin'] = $this->language->get('text_linkedin');
        $data['text_twitter'] = $this->language->get('text_twitter');
        $data['text_yahoo'] = $this->language->get('text_yahoo');
        $data['text_amazon'] = $this->language->get('text_amazon');
        $data['text_instagram'] = $this->language->get('text_instagram');
        $data['text_paypal'] = $this->language->get('text_paypal');
        $data['text_live'] = $this->language->get('text_live');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['cancel'] = $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token, true);

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_status'] = $this->language->get('entry_status');

        //tab facebook
        $data['facebook_status_info'] = $this->language->get('facebook_status_info');
        $data['facebook_api_id'] = $this->language->get('facebook_api_id');
        $data['facebook_api_id_info'] = $this->language->get('facebook_api_id_info');
        $data['facebook_api_secret'] = $this->language->get('facebook_api_secret');
        $data['facebook_api_secret_info'] = $this->language->get('facebook_api_secret_info');

        $data['google_status_info'] = $this->language->get('google_status_info');
        $data['google_client_id'] = $this->language->get('google_client_id');
        $data['google_client_id_info'] = $this->language->get('google_client_id_info');
        $data['google_client_secret'] = $this->language->get('google_client_secret');
        $data['google_client_secret_info'] = $this->language->get('google_client_secret_info');

        $data['linkedin_status_info'] = $this->language->get('linkedin_status_info');
        $data['linkedin_api_key'] = $this->language->get('linkedin_api_key');
        $data['linkedin_api_key_info'] = $this->language->get('linkedin_api_key_info');
        $data['linkedin_secret_key'] = $this->language->get('linkedin_secret_key');
        $data['linkedin_secret_key_info'] = $this->language->get('linkedin_secret_key_info');

        $data['live_status_info'] = $this->language->get('live_status_info');
        $data['live_client_id'] = $this->language->get('live_client_id');
        $data['live_client_id_info'] = $this->language->get('live_client_id_info');
        $data['live_client_secret'] = $this->language->get('live_client_secret');
        $data['live_client_secret_info'] = $this->language->get('live_client_secret_info');

        $data['twitter_status_info'] = $this->language->get('twitter_status_info');
        $data['twitter_api_key'] = $this->language->get('twitter_api_key');
        $data['twitter_api_key_info'] = $this->language->get('twitter_api_key_info');
        $data['twitter_api_secret'] = $this->language->get('twitter_api_secret');
        $data['twitter_api_secret_info'] = $this->language->get('twitter_api_secret_info');

        $data['yahoo_status_info'] = $this->language->get('yahoo_status_info');
        $data['yahoo_consumer_key'] = $this->language->get('yahoo_consumer_key');
        $data['yahoo_consumer_key_info'] = $this->language->get('yahoo_consumer_key_info');
        $data['yahoo_consumer_secret'] = $this->language->get('yahoo_consumer_secret');
        $data['yahoo_consumer_secret_info'] = $this->language->get('yahoo_consumer_secret_info');

        $data['amazon_status_info'] = $this->language->get('amazon_status_info');
        $data['amazon_client_id'] = $this->language->get('amazon_client_id');
        $data['amazon_client_id_info'] = $this->language->get('amazon_client_id_info');
        $data['amazon_client_secret'] = $this->language->get('amazon_client_secret');
        $data['amazon_client_secret_info'] = $this->language->get('amazon_client_secret_info');

        $data['instagram_status_info'] = $this->language->get('instagram_status_info');
        $data['instagram_client_id'] = $this->language->get('instagram_client_id');
        $data['instagram_client_id_info'] = $this->language->get('instagram_client_id_info');
        $data['instagram_client_secret'] = $this->language->get('instagram_client_secret');
        $data['instagram_client_secret_info'] = $this->language->get('instagram_client_secret_info');

        $data['paypal_status_info'] = $this->language->get('paypal_status_info');
        $data['paypal_client_id'] = $this->language->get('paypal_client_id');
        $data['paypal_client_id_info'] = $this->language->get('paypal_client_id_info');
        $data['paypal_client_secret'] = $this->language->get('paypal_client_secret');
        $data['paypal_client_secret_info'] = $this->language->get('paypal_client_secret_info');

        $data['get_google_id_link'] = 'https://console.developers.google.com/project';
        $data['get_google_id'] = $this->language->get('get_google_id');

        $data['error_empty_field'] = $this->language->get('error_empty_field');
        $data['error_message'] = $this->language->get('error_message');
        $data['success_message'] = $this->language->get('text_success');

        $data['ocsociallogin_facebook_status'] = '0';
        $data['ocsociallogin_facebook_api_id'] = '';
        $data['ocsociallogin_facebook_api_secret'] = '';

        if (isset($this->request->post['ocsociallogin_setting_google_status'])) {
            $data['ocsociallogin_google_status'] = $this->request->post['ocsociallogin_setting_google_status'];
        } elseif (!empty($ocsociallogin_setting)) {
            $data['ocsociallogin_google_status'] = $ocsociallogin_setting['ocsociallogin_setting_google_status'];
        } else {
            $data['ocsociallogin_google_status'] = '0';
        }

        if (isset($this->request->post['ocsociallogin_setting_google_client_id'])) {
            $data['ocsociallogin_google_client_id'] = $this->request->post['ocsociallogin_setting_google_client_id'];
        } elseif (!empty($ocsociallogin_setting)) {
            $data['ocsociallogin_google_client_id'] = $ocsociallogin_setting['ocsociallogin_setting_google_client_id'];
        } else {
            $data['ocsociallogin_google_client_id'] = '';
        }

        if (isset($this->request->post['ocsociallogin_setting_google_client_secret'])) {
            $data['ocsociallogin_google_client_secret'] = $this->request->post['ocsociallogin_setting_google_client_secret'];
        } elseif (!empty($ocsociallogin_setting)) {
            $data['ocsociallogin_google_client_secret'] = $ocsociallogin_setting['ocsociallogin_setting_google_client_secret'];
        } else {
            $data['ocsociallogin_google_client_secret'] = '';
        }

        $data['ocsociallogin_linkedin_status'] = '0';
        $data['ocsociallogin_linkedin_api_key'] = '';
        $data['ocsociallogin_linkedin_secret_key'] = '';
        $data['ocsociallogin_twitter_status'] = '0';
        $data['ocsociallogin_twitter_api_key'] = '';
        $data['ocsociallogin_twitter_api_secret'] = '';
        $data['ocsociallogin_yahoo_status'] = '0';
        $data['ocsociallogin_yahoo_consumer_key'] = '';
        $data['ocsociallogin_yahoo_consumer_secret'] = '';
        $data['ocsociallogin_amazon_status'] = '0';
        $data['ocsociallogin_amazon_client_id'] = '';
        $data['ocsociallogin_amazon_client_secret'] = '';
        $data['ocsociallogin_instagram_status'] = '0';
        $data['ocsociallogin_instagram_client_secret'] = '';
        $data['ocsociallogin_instagram_client_id'] = '';
        $data['ocsociallogin_paypal_status'] = '0';
        $data['ocsociallogin_paypal_client_id'] = '';
        $data['ocsociallogin_paypal_client_secret'] = '';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $active_tab['active'] = 2;
        $data['tab_common'] = $this->load->controller($this->module_path . '/ocsociallogin/tabs', $active_tab);

        $data_swticher['current_url'] = $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token, true);
        $data_swticher['store_id'] = $store_id;
        $data['storeSwticher'] = $this->load->controller($this->module_path . '/ocsociallogin/storeSwticher', $data_swticher);


        if (VERSION < '2.2.0') {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/configuration_account.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/configuration_account', $data));
        }
    }

    public function configuration_modules()
    {
        $this->load->language($this->module_path . '/ocsociallogin');
        $this->document->setTitle($this->language->get('heading_title_main'));
        $this->load->model('setting/module');
        $this->load->model('ocsociallogin/ocsociallogin');
        $store_id = 0;
        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        }
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }
        $data['text_edit'] = $this->language->get('tab_configure_modules');
        $data['heading_title'] = $this->language->get('heading_title');
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('tab_configure_modules'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin/configuration_modules', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );
        $data['cancel'] = $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
        $data['add_new'] = $this->url->link($this->module_path . '/ocsociallogin/configuration_modules_form', $this->session_token_key . '=' . $this->session_token, true);
        ;
        $data['text_add_new'] = $this->language->get('text_add_new');
        $data['tab_configuration_modules'] = $this->language->get('tab_configuration_modules');
        $data['text_name'] = $this->language->get('text_name');
        $data['text_layout'] = $this->language->get('text_layout');
        $data['text_sortorder'] = $this->language->get('text_sortorder');
        $data['text_title'] = $this->language->get('text_title');
        $data['text_button_size'] = $this->language->get('text_button_size');
        $data['text_action'] = $this->language->get('text_action');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['no_result_message'] = $this->language->get('no_result_message');
        $data['text_title_status'] = $this->language->get('text_title_status');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_layout'] = $this->language->get('text_layout');
        $data['text_small'] = $this->language->get('text_small');
        $data['text_large'] = $this->language->get('text_large');
        $data['text_position'] = $this->language->get('text_position');
        $data['text_content_top'] = $this->language->get('text_content_top');
        $data['text_content_bottom'] = $this->language->get('text_content_bottom');
        $data['text_column_left'] = $this->language->get('text_column_left');
        $data['text_column_right'] = $this->language->get('text_column_right');
        $data['text_button_layout'] = $this->language->get('text_button_layout');
        $results = $this->model_setting_module->getModulesByCode('ocsociallogin_module');
        foreach ($results as $result) {
            $module_info = $this->model_setting_module->getModule($result['module_id']);
            $data['modules'][] = array(
                'name' => $module_info['name'],
                'sortorder' => $module_info['sort_order'],
                'title_status' => $module_info['title_status'],
                'buttonlayout' => $this->model_ocsociallogin_ocsociallogin->button_layout_name($module_info['button_layout']),
                'status' => $module_info['status'],
                'position' => $module_info['position'],
                'layout_name' => $this->model_ocsociallogin_ocsociallogin->layout_name($module_info['layout_id']),
                'edit' => $this->url->link($this->module_path . '/ocsociallogin/configuration_modules_form', $this->session_token_key . '=' . $this->session_token . '&module_id=' . $result['module_id'], true),
                'delete' => $this->url->link($this->module_path . '/ocsociallogin/configuration_modules_delete', $this->session_token_key . '=' . $this->session_token . '&module_id=' . $result['module_id'], true),
            );
        }
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $active_tab['active'] = 3;
        $data['tab_common'] = $this->load->controller($this->module_path . '/ocsociallogin/tabs', $active_tab);

        $data_swticher['current_url'] = $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token, true);
        $data_swticher['store_id'] = $store_id;
        $data['storeSwticher'] = $this->load->controller($this->module_path . '/ocsociallogin/storeSwticher', $data_swticher);


        if (VERSION < '2.2.0') {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/configuration_modules.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/configuration_modules', $data));
        }
    }

    public function configuration_modules_form()
    {
        $this->load->language($this->module_path . '/ocsociallogin');
        $this->document->setTitle($this->language->get('heading_title_main'));
        $store_id = 0;
        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        }
        $this->load->model('setting/module');
        $this->load->model('ocsociallogin/ocsociallogin');

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            if (!isset($this->request->get['module_id'])) {

                $query = $this->model_ocsociallogin_ocsociallogin->add_module_check($this->request->post, 'ocsociallogin_module');
                if ($query->num_rows == 0) {

                    $querycheckmodule = $this->model_ocsociallogin_ocsociallogin->checkaddmodule('ocsociallogin_module', $this->request->post);
                    if (!$querycheckmodule) {
                        $this->model_ocsociallogin_ocsociallogin->addModule('ocsociallogin_module', $this->request->post);

                        $id = $this->model_ocsociallogin_ocsociallogin->get_id($this->request->post, 'ocsociallogin_module');
                        $this->model_ocsociallogin_ocsociallogin->insert_layout_module($this->request->post, $id, 'ocsociallogin_module');
                        $this->session->data['success'] = $this->language->get('add_module_successfully');
                        $this->response->redirect($this->url->link($this->module_path . '/ocsociallogin/configuration_modules', $this->session_token_key . '=' . $this->session_token, true));
                    } else {
                        $data['already_exist'] = $this->language->get('module_already_exists_by_layout');
                    }
                } else {
                    $data['already_exist'] = $this->language->get('module_already_exist');
                }
            } else {
                $query = $this->model_ocsociallogin_ocsociallogin->update_module_check($this->request->get['module_id'], $this->request->post, 'ocsociallogin_module');

                if ($query->num_rows == 0) {
                    $querycheckmodule = $this->model_ocsociallogin_ocsociallogin->checkupdatemodule('ocsociallogin_module', $this->request->post, $this->request->get['module_id']);
                    if (!$querycheckmodule) {
                        $this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
                        $this->model_ocsociallogin_ocsociallogin->update_layout_module($this->request->post, $this->request->get['module_id'], 'ocsociallogin_module');
                        $this->session->data['success'] = $this->language->get('edit_module_successfully');
                        $this->response->redirect($this->url->link($this->module_path . '/ocsociallogin/configuration_modules', $this->session_token_key . '=' . $this->session_token, true));
                    } else {
                        $data['already_exist'] = $this->language->get('module_already_exists_by_layout');
                    }
                } else {
                    $data['already_exist'] = $this->language->get('module_already_exist');
                }
            }
        }
        if (isset($this->request->get['module_id'])) {
            $data['text_edit'] = $this->language->get('text_edit_module');
        } else {
            $data['text_edit'] = $this->language->get('text_add_module');
        }
        $data['heading_title'] = $this->language->get('heading_title');
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('tab_configure_modules'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin/configuration_modules', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );
        if (isset($this->request->get['module_id'])) {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_edit_module'),
                'href' => $this->url->link($this->module_path . '/ocsociallogin/configuration_modules_form', $this->session_token_key . '=' . $this->session_token . '&module_id=' . $this->request->get['module_id'], true)
            );
        } else {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_add_module'),
                'href' => $this->url->link($this->module_path . '/ocsociallogin/configuration_modules_form', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
            );
        }
        $data['configuration_modules_form'] = $this->language->get('configuration_modules_form');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_name'] = $this->language->get('text_name');
        $data['text_position'] = $this->language->get('text_position');
        $data['cancel'] = $this->url->link($this->module_path . '/ocsociallogin/configuration_modules', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
        $data['text_content_top'] = $this->language->get('text_content_top');
        $data['text_content_bottom'] = $this->language->get('text_content_bottom');
        $data['text_column_left'] = $this->language->get('text_column_left');
        $data['text_column_right'] = $this->language->get('text_column_right');
        $data['text_header'] = $this->language->get('text_header');
        $data['text_footer'] = $this->language->get('text_footer');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['text_module_name'] = $this->language->get('text_module_name');
        $data['text_layout'] = $this->language->get('text_layout');
        $data['text_sortorder'] = $this->language->get('text_sortorder');
        $data['text_title'] = $this->language->get('text_title');
        $data['text_title_status'] = $this->language->get('text_title_status');
        $data['text_button_size'] = $this->language->get('text_button_size');
        $data['text_small'] = $this->language->get('text_small');
        $data['text_large'] = $this->language->get('text_large');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['error_empty_field'] = $this->language->get('error_empty_field');
        $data['error_number_field'] = $this->language->get('error_number_field');
        $data['text_button_layout'] = $this->language->get('text_button_layout');
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();
        $this->load->model('design/layout');
        $data['layouts'] = $this->model_design_layout->getLayouts();
        $data['all_icon_layout'] = $this->model_ocsociallogin_ocsociallogin->get_all_icon_layout_name();
        $data['form_error_message'] = $this->language->get('form_error_message');
        $data['error_button_layout'] = $this->language->get('error_button_layout');
        $data['text_select_it'] = $this->language->get('text_select_it');

        if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
        }

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($module_info)) {
            $data['name'] = $module_info['name'];
        } else {
            $data['name'] = '';
        }


        foreach ($data['languages'] as $language) {
            if (isset($this->request->post['title'])) {
                $data['title'][$language['language_id']] = $this->request->post['title'][$language['language_id']];
            } elseif (!empty($module_info)) {
                $data['title'][$language['language_id']] = $module_info['title'][$language['language_id']];
            } else {
                $data['title'][$language['language_id']] = '';
            }
        }

        if (isset($this->request->post['title_status'])) {
            $data['title_status'] = $this->request->post['title_status'];
        } elseif (!empty($module_info)) {
            $data['title_status'] = $module_info['title_status'];
        } else {
            $data['title_status'] = '1';
        }

        if (isset($this->request->post['sort_order'])) {
            $data['sortorder'] = $this->request->post['sort_order'];
        } elseif (!empty($module_info)) {
            $data['sortorder'] = $module_info['sort_order'];
        } else {
            $data['sortorder'] = '';
        }

        if (isset($this->request->post['button_layout'])) {
            $data['button_layout'] = $this->request->post['button_layout'];
        } elseif (!empty($module_info)) {
            $data['button_layout'] = $module_info['button_layout'];
        } else {
            $data['button_layout'] = 'small';
        }
        if (isset($this->request->post['layout_id'])) {
            $data['layout_id'] = $this->request->post['layout_id'];
        } elseif (!empty($module_info)) {
            $data['layout_id'] = $module_info['layout_id'];
        } else {
            $data['layout_id'] = '0';
        }
        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($module_info)) {
            $data['status'] = $module_info['status'];
        } else {
            $data['status'] = '1';
        }
        if (isset($this->request->post['position'])) {
            $data['position'] = $this->request->post['position'];
        } elseif (!empty($module_info)) {
            $data['position'] = $module_info['position'];
        } else {
            $data['position'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $active_tab['active'] = 3;
        $data['tab_common'] = $this->load->controller($this->module_path . '/ocsociallogin/tabs', $active_tab);

        if (VERSION < '2.2.0') {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/configuration_modules_form.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/configuration_modules_form', $data));
        }
    }

    public function configuration_modules_delete()
    {
        $this->load->model('setting/module');
        $this->load->language($this->module_path . '/ocsociallogin');
        $module_info = $this->model_setting_module->deleteModule($this->request->get['module_id']);
        $this->session->data['success'] = $this->language->get('module_delete_successfully');
        $this->response->redirect($this->url->link($this->module_path . '/ocsociallogin/configuration_modules', $this->session_token_key . '=' . $this->session_token, true));
    }

    public function configure_icons()
    {

        $this->load->language($this->module_path . '/ocsociallogin');
        $this->document->setTitle($this->language->get('heading_title_main'));
        $this->load->model('setting/module');
        $this->load->model('ocsociallogin/ocsociallogin');
        $store_id = 0;
        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        }
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }
        if (isset($this->session->data['warning'])) {
            $data['warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        }
        $data['text_edit'] = $this->language->get('tab_configure_icons');
        $data['heading_title'] = $this->language->get('heading_title');
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('tab_configure_icons'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin/configuration_icons', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );
        $data['cancel'] = $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
        $data['add_new'] = $this->url->link($this->module_path . '/ocsociallogin/configure_icons_form', $this->session_token_key . '=' . $this->session_token, true);
        ;
        $data['text_add_new'] = $this->language->get('text_add_new');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['no_result_message'] = $this->language->get('no_result_message');
        $data['text_name'] = $this->language->get('text_name');
        $data['text_layout'] = $this->language->get('text_layout');
        $data['text_sortorder'] = $this->language->get('text_sortorder');
        $data['text_title'] = $this->language->get('text_title');
        $data['text_button_size'] = $this->language->get('text_button_size');
        $data['text_action'] = $this->language->get('text_action');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['text_icon_type'] = $this->language->get('text_icon_type');
        $data['text_hover_effect'] = $this->language->get('text_hover_effect');
        $data['text_alignment'] = $this->language->get('text_alignment');
        $data['text_horizontal'] = $this->language->get('text_horizontal');
        $data['text_vertical'] = $this->language->get('text_vertical');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['text_small_circular'] = $this->language->get('text_small_circular');
        $data['text_small_rectangular'] = $this->language->get('text_small_rectangular');
        $data['text_small_rounded'] = $this->language->get('text_small_rounded');
        $data['text_large_rounded'] = $this->language->get('text_large_rounded');
        $data['text_large_rectangular'] = $this->language->get('text_large_rectangular');
        $data['text_highlight'] = $this->language->get('text_highlight');
        $data['text_zoom'] = $this->language->get('text_zoom');
        $data['text_rotate'] = $this->language->get('text_rotate');
        $data['layout_id'] = $this->language->get('layout_id');
        $data['text_layout_name'] = $this->language->get('text_layout_name');
        $results = $this->model_ocsociallogin_ocsociallogin->select_all_icon_layout();

        foreach ($results as $result) {

            $data['icons'][] = array(
                'id' => $result['id'],
                'name' => $result['name'],
                'icon_type' => $result['icon_type'],
                'hover_effect' => $result['hover_effect'],
                'alignment' => $result['alignment'],
                'edit' => $this->url->link($this->module_path . '/ocsociallogin/configure_icons_form', $this->session_token_key . '=' . $this->session_token . '&id=' . $result['id'], true),
                'delete' => $this->url->link($this->module_path . '/ocsociallogin/configure_icons_delete', $this->session_token_key . '=' . $this->session_token . '&id=' . $result['id'], true),
            );
        }


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $active_tab['active'] = 4;
        $data['tab_common'] = $this->load->controller($this->module_path . '/ocsociallogin/tabs', $active_tab);


        if (VERSION < '2.2.0') {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/configure_icons.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/configure_icons', $data));
        }
    }

    public function configure_icons_form()
    {
        $this->load->language($this->module_path . '/ocsociallogin');
        $this->document->setTitle($this->language->get('heading_title_main'));
        $store_id = 0;
        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        }

        $this->load->model('setting/module');
        $this->load->model('ocsociallogin/ocsociallogin');

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            if (isset($this->request->get['id'])) {
                $query = $this->model_ocsociallogin_ocsociallogin->update_icon_layout_check($this->request->post, $this->request->get['id']);
                if ($query->num_rows == 0) {
                    $query = $this->model_ocsociallogin_ocsociallogin->update_icon_layout($this->request->post, $this->request->get['id']);
                    $this->session->data['success'] = $this->language->get('icon_layout_update_successfully');
                    $this->response->redirect($this->url->link($this->module_path . '/ocsociallogin/configure_icons', $this->session_token_key . '=' . $this->session_token, true));
                } else {
                    $data['already_exist'] = $this->language->get('icon_already_exist');
                }
            } else {
                $query = $this->model_ocsociallogin_ocsociallogin->add_icon_layout_check($this->request->post);

                if ($query->num_rows == 0) {
                    $query = $this->model_ocsociallogin_ocsociallogin->add_icon_layout($this->request->post);
                    $this->session->data['success'] = $this->language->get('icon_layout_add_successfully');
                    $this->response->redirect($this->url->link($this->module_path . '/ocsociallogin/configure_icons', $this->session_token_key . '=' . $this->session_token, true));
                } else {
                    $data['already_exist'] = $this->language->get('icon_layout_already_exist');
                }
            }
        }

        if (isset($this->request->get['id'])) {
            $icon_layout = $this->model_ocsociallogin_ocsociallogin->get_icon_layout($this->request->get['id']);
        }

        if (isset($this->request->get['id'])) {
            $data['text_edit'] = $this->language->get('text_edit_icon_layout');
        } else {
            $data['text_edit'] = $this->language->get('text_add_icon_layout');
        }
        $data['heading_title'] = $this->language->get('heading_title');
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('tab_configure_icons'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin/configure_icons', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
        );
        if (isset($this->request->get['id'])) {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_edit_icon_layout'),
                'href' => $this->url->link($this->module_path . '/ocsociallogin/configure_icons_form', $this->session_token_key . '=' . $this->session_token . '&id=' . $this->request->get['id'], true)
            );
        } else {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_add_icon_layout'),
                'href' => $this->url->link($this->module_path . '/ocsociallogin/configure_icons_form', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true)
            );
        }
        $data['cancel'] = $this->url->link($this->module_path . '/ocsociallogin/configure_icons', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_save'] = $this->language->get('button_save');
        $data['text_name'] = $this->language->get('text_name');
        $data['text_button_layout_name'] = $this->language->get('text_button_layout_name');
        $data['text_choose_logins'] = $this->language->get('text_choose_logins');
        $data['title_choose_logins'] = $this->language->get('title_choose_logins');
        $data['text_icon_type'] = $this->language->get('text_icon_type');
        $data['text_hover_effect'] = $this->language->get('text_hover_effect');
        $data['text_facebook'] = $this->language->get('text_facebook');
        $data['text_google'] = $this->language->get('text_google');
        $data['text_linkedin'] = $this->language->get('text_linkedin');
        $data['text_twitter'] = $this->language->get('text_twitter');
        $data['text_yahoo'] = $this->language->get('text_yahoo');
        $data['text_amazon'] = $this->language->get('text_amazon');
        $data['text_instagram'] = $this->language->get('text_instagram');
        $data['text_paypal'] = $this->language->get('text_paypal');
        $data['text_live'] = $this->language->get('text_live');
        $data['text_small_circular'] = $this->language->get('text_small_circular');
        $data['text_small_rectangular'] = $this->language->get('text_small_rectangular');
        $data['text_small_rounded'] = $this->language->get('text_small_rounded');
        $data['text_large_rounded'] = $this->language->get('text_large_rounded');
        $data['text_large_rectangular'] = $this->language->get('text_large_rectangular');
        $data['text_highlight'] = $this->language->get('text_highlight');
        $data['text_zoom'] = $this->language->get('text_zoom');
        $data['text_rotate'] = $this->language->get('text_rotate');
        $data['text_button_color'] = $this->language->get('button_color');
        $data['text_customize'] = $this->language->get('text_customize');
        $data['default'] = $this->language->get('default');
        $data['text_icon_color'] = $this->language->get('text_icon_color');
        $data['text_text_color'] = $this->language->get('text_text_color');
        $data['text_hover_color'] = $this->language->get('text_hover_color');
        $data['text_preview'] = $this->language->get('text_preview');
        $data['error_empty_field'] = $this->language->get('error_empty_field');
        $data['text_alignment'] = $this->language->get('text_alignment');
        $data['text_horizontal'] = $this->language->get('text_horizontal');
        $data['text_vertical'] = $this->language->get('text_vertical');
        $data['error_message_icons_layout_form'] = $this->language->get('error_message_icons_layout_form');
        $data['form_error_message'] = $this->language->get('form_error_message');
        $data['login_with_google'] = $this->language->get('login_with_google');
        $data['login_with_linked'] = $this->language->get('login_with_linked');
        $data['login_with_live'] = $this->language->get('login_with_live');
        $data['login_with_twitter'] = $this->language->get('login_with_twitter');
        $data['login_with_yahoo'] = $this->language->get('login_with_yahoo');
        $data['login_with_amazon'] = $this->language->get('login_with_amazon');
        $data['login_with_instagram'] = $this->language->get('login_with_instagram');
        $data['login_with_paypal'] = $this->language->get('login_with_paypal');
        $data['login_with_facebook'] = $this->language->get('login_with_facebook');

        if (isset($this->request->post['layout_name'])) {
            $data['layout_name'] = $this->request->post['layout_name'];
        } elseif (isset($icon_layout) && !empty($icon_layout)) {
            $data['layout_name'] = $icon_layout['name'];
        } else {
            $data['layout_name'] = '';
        }

        if (isset($this->request->post['choose_login'])) {
            $data['choose_login'] = $this->request->post['choose_login'];
        } elseif (!empty($icon_layout)) {
            if (!empty($icon_layout['choose_login'])) {
                $data['choose_login'] = $icon_layout['choose_login'];
            } else {
                $data['choose_login'] = array();
            }
        } else {
            $data['choose_login'] = ["facebook", "google", "linkedin", "live", "twitter", "yahoo", "amazon", "instagram", "paypal"];
        }

        if (isset($this->request->post['icon_type'])) {
            $data['icon_type'] = $this->request->post['icon_type'];
        } elseif (!empty($icon_layout)) {
            $data['icon_type'] = $icon_layout['icon_type'];
        } else {
            $data['icon_type'] = 'small_circular';
        }

        if (isset($this->request->post['hover_effect'])) {
            $data['hover_effect'] = $this->request->post['hover_effect'];
        } elseif (!empty($icon_layout)) {
            $data['hover_effect'] = $icon_layout['hover_effect'];
        } else {
            $data['hover_effect'] = 'highlight';
        }

        if (isset($this->request->post['alignment'])) {
            $data['alignment'] = $this->request->post['alignment'];
        } elseif (!empty($icon_layout)) {
            $data['alignment'] = $icon_layout['alignment'];
        } else {
            $data['alignment'] = 'horizontal';
        }
        if (isset($this->request->post['button_color'])) {
            $data['button_color'] = $this->request->post['button_color'];
        } elseif (!empty($icon_layout)) {
            $data['button_color'] = $icon_layout['button_color'];
        } else {
            $data['button_color'] = '0';
        }

        if (isset($this->request->post['color']['facebook'])) {
            $data['facebook_icon_color'] = $this->request->post['color']['facebook']['icon'];
        } elseif (!empty($icon_layout['color']['facebook'])) {
            $data['facebook_icon_color'] = $icon_layout['color']['facebook']['icon'];
        } else {
            $data['facebook_icon_color'] = '#3C599F';
        }


        if (isset($this->request->post['color']['facebook'])) {
            $data['facebook_text_color'] = $this->request->post['color']['facebook']['text'];
        } elseif (!empty($icon_layout['color']['facebook'])) {
            $data['facebook_text_color'] = $icon_layout['color']['facebook']['text'];
        } else {
            $data['facebook_text_color'] = '#ffffff';
        }

        if (isset($this->request->post['color'])) {
            $data['google_icon_color'] = $this->request->post['color']['google']['icon'];
        } elseif (!empty($icon_layout['color']['google'])) {
            $data['google_icon_color'] = $icon_layout['color']['google']['icon'];
        } else {
            $data['google_icon_color'] = '#3f85f4';
        }


        if (isset($this->request->post['color'])) {
            $data['google_text_color'] = $this->request->post['color']['google']['text'];
        } elseif (!empty($icon_layout ['color']['google'])) {
            $data['google_text_color'] = $icon_layout['color']['google']['text'];
        } else {
            $data['google_text_color'] = '#ffffff';
        }

        if (isset($this->request->post['color'])) {
            $data['linkedin_icon_color'] = $this->request->post['color']['linkedin']['icon'];
        } elseif (!empty($icon_layout['color']['linkedin'])) {
            $data['linkedin_icon_color'] = $icon_layout['color']['linkedin']['icon'];
        } else {
            $data['linkedin_icon_color'] = '#0177b5';
        }



        if (isset($this->request->post['color'])) {
            $data['linkedin_text_color'] = $this->request->post['color']['linkedin']['text'];
        } elseif (!empty($icon_layout ['color']['linkedin'])) {
            $data['linkedin_text_color'] = $icon_layout['color']['linkedin']['text'];
        } else {
            $data['linkedin_text_color'] = '#ffffff';
        }

        if (isset($this->request->post['color'])) {
            $data['twitter_icon_color'] = $this->request->post['color']['twitter']['icon'];
        } elseif (!empty($icon_layout ['color']['twitter'])) {
            $data['twitter_icon_color'] = $icon_layout['color']['twitter']['icon'];
        } else {
            $data['twitter_icon_color'] = '#32CCFE';
        }


        if (isset($this->request->post['color'])) {
            $data['twitter_text_color'] = $this->request->post['color']['twitter']['text'];
        } elseif (!empty($icon_layout['color']['twitter'])) {
            $data['twitter_text_color'] = $icon_layout['color']['twitter']['text'];
        } else {
            $data['twitter_text_color'] = '#ffffff';
        }


        if (isset($this->request->post['color'])) {
            $data['live_icon_color'] = $this->request->post['color']['live']['icon'];
        } elseif (!empty($icon_layout['color']['live'])) {
            $data['live_icon_color'] = $icon_layout['color']['live']['icon'];
        } else {
            $data['live_icon_color'] = '#12B6F3';
        }



        if (isset($this->request->post['color'])) {
            $data['live_text_color'] = $this->request->post['color']['live']['text'];
        } elseif (!empty($icon_layout['color']['live'])) {
            $data['live_text_color'] = $icon_layout['color']['live']['text'];
        } else {
            $data['live_text_color'] = '#ffffff';
        }

        if (isset($this->request->post['color'])) {
            $data['amazon_icon_color'] = $this->request->post['color']['amazon']['icon'];
        } elseif (!empty($icon_layout['color']['amazon'])) {
            $data['amazon_icon_color'] = $icon_layout['color']['amazon']['icon'];
        } else {
            $data['amazon_icon_color'] = '#000000';
        }


        if (isset($this->request->post['color'])) {
            $data['amazon_text_color'] = $this->request->post['color']['amazon']['text'];
        } elseif (!empty($icon_layout['color']['amazon'])) {
            $data['amazon_text_color'] = $icon_layout['color']['amazon']['text'];
        } else {
            $data['amazon_text_color'] = '#ffffff';
        }

        if (isset($this->request->post['color'])) {
            $data['yahoo_icon_color'] = $this->request->post['color']['yahoo']['icon'];
        } elseif (!empty($icon_layout['color']['yahoo'])) {
            $data['yahoo_icon_color'] = $icon_layout['color']['yahoo']['icon'];
        } else {
            $data['yahoo_icon_color'] = '#5210c4';
        }



        if (isset($this->request->post['color'])) {
            $data['yahoo_text_color'] = $this->request->post['color']['yahoo']['text'];
        } elseif (!empty($icon_layout['color']['yahoo'])) {
            $data['yahoo_text_color'] = $icon_layout['color']['yahoo']['text'];
        } else {
            $data['yahoo_text_color'] = '#ffffff';
        }

        if (isset($this->request->post['color'])) {
            $data['instagram_icon_color'] = $this->request->post['color']['instagram']['icon'];
        } elseif (!empty($icon_layout['color']['instagram'])) {
            $data['instagram_icon_color'] = $icon_layout['color']['instagram']['icon'];
        } else {
            $data['instagram_icon_color'] = '#3d6b92';
        }



        if (isset($this->request->post['color'])) {
            $data['instagram_text_color'] = $this->request->post['color']['instagram']['text'];
        } elseif (!empty($icon_layout['color']['instagram'])) {
            $data['instagram_text_color'] = $icon_layout['color']['instagram']['text'];
        } else {
            $data['instagram_text_color'] = '#ffffff';
        }

        if (isset($this->request->post['color'])) {
            $data['paypal_icon_color'] = $this->request->post['color']['paypal']['icon'];
        } elseif (!empty($icon_layout['color']['paypal'])) {
            $data['paypal_icon_color'] = $icon_layout['color']['paypal']['icon'];
        } else {
            $data['paypal_icon_color'] = '#009cde';
        }



        if (isset($this->request->post['color'])) {
            $data['paypal_text_color'] = $this->request->post['color']['paypal']['text'];
        } elseif (!empty($icon_layout['color']['paypal'])) {
            $data['paypal_text_color'] = $icon_layout['color']['paypal']['text'];
        } else {
            $data['paypal_text_color'] = '#ffffff';
        }

        $data['kbsocialmessanger_background_color'] = '#70fff0';
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $active_tab['active'] = 4;
        $data['tab_common'] = $this->load->controller($this->module_path . '/ocsociallogin/tabs', $active_tab);

        if (VERSION < '2.2.0') {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/configure_icons_form.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/configure_icons_form', $data));
        }
    }

    public function configure_icons_delete()
    {
        $this->load->language($this->module_path . '/ocsociallogin');
        $this->load->model('ocsociallogin/ocsociallogin');
        $check_icon = $this->model_ocsociallogin_ocsociallogin->delete_icon_layout_check($this->request->get['id']);
        if ($check_icon) {
            $this->session->data['warning'] = $this->language->get('icon_layout_delete_warning');
        } else {
            $this->model_ocsociallogin_ocsociallogin->delete_icon_layout($this->request->get['id']);
            $this->session->data['success'] = $this->language->get('icon_layout_delete_successfully');
        }
        $this->response->redirect($this->url->link($this->module_path . '/ocsociallogin/configure_icons', $this->session_token_key . '=' . $this->session_token, true));
    }

    public function statistics()
    {
        $store_id = 0;
        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        }
        $this->load->language($this->module_path . '/ocsociallogin');
        $this->document->setTitle($this->language->get('heading_title_main'));
        $this->load->model('ocsociallogin/ocsociallogin');
        $data['text_edit'] = $this->language->get('tab_statistics');
        $data['heading_title'] = $this->language->get('heading_title');
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->session_token_key . '=' . $this->session_token, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('tab_statistics'),
            'href' => $this->url->link($this->module_path . '/ocsociallogin/statistics', $this->session_token_key . '=' . $this->session_token, true)
        );

        $data['cancel'] = $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token, true);
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['text_srno'] = $this->language->get('text_srno');
        $data['text_account_type'] = $this->language->get('text_account_type');
        $data['text_register_user'] = $this->language->get('text_register_user');
        $data['text_login_user'] = $this->language->get('text_login_user');
        $data['no_result_message'] = $this->language->get('no_result_message');
        $data['text_registered_through'] = $this->language->get('text_registered_through');
        $data['text_full_name'] = $this->language->get('text_full_name');
        $data['text_email'] = $this->language->get('text_email');
        $data['text_login_count'] = $this->language->get('text_login_count');
        $data['text_total_registration'] = $this->language->get('text_total_registration');
        $data['button_search'] = $this->language->get('text_search');
        $data['text_email'] = $this->language->get('text_email');
        if (!isset($this->request->get['page'])) {
            $page = 1;
        }
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        }
        if (isset($this->request->get['email'])) {
            $search_email = $this->request->get['email'];
            $data['search_email'] = $this->request->get['email'];
        } else {
            $search_email = null;
            $data['search_email'] = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'id';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        $filter_data = array(
            'email' => $search_email,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );
        $total = $this->model_ocsociallogin_ocsociallogin->counttotalregister($filter_data);
        $total = $total['total'];


        $url = '';
        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }


        $data['sort_name'] = $this->url->link($this->module_path . '/ocsociallogin/statistics', $this->session_token_key . '=' . $this->session_token . '&sort=name&store_id=' . $store_id . $url, true);
        $data['sort_email'] = $this->url->link($this->module_path . '/ocsociallogin/statistics', $this->session_token_key . '=' . $this->session_token . '&sort=email&store_id=' . $store_id . $url, true);
        $data['sort_registration'] = $this->url->link($this->module_path . '/ocsociallogin/statistics', $this->session_token_key . '=' . $this->session_token . '&sort=type&store_id=' . $store_id . $url, true);

        $results = $this->model_ocsociallogin_ocsociallogin->countregistercustomer();

        $data['register_detail'] = $results;
        $result_login = $this->model_ocsociallogin_ocsociallogin->countlogincustomer();

        $data['login_detail'] = $result_login;

        foreach ($results as $result) {
            $query = $this->model_ocsociallogin_ocsociallogin->countlogincustomertype($result['type']);

            $data['result'][] = array(
                'type' => $result['type'],
                'register' => $result['count'],
                'login' => $query['count'],
            );
        }

        $login_full_details = $this->model_ocsociallogin_ocsociallogin->customerlist($filter_data);
        foreach ($login_full_details as $result) {

            $totalloginbyid = $this->model_ocsociallogin_ocsociallogin->totalloginbyid($result['user_id']);
            $data['result_detail'][] = array(
                'name' => $result['firstname'] . ' ' . $result['lastname'],
                'email' => $result['email'],
                'login_via' => $result['type'],
                'total_login' => $totalloginbyid['count'],
            );
        }
        
        $url = '';
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link($this->module_path . '/ocsociallogin/statistics', $this->session_token_key . '=' . $this->session_token . $url . '&page={page}&store_id=' . $store_id, true);
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $pagination->limit) + 1 : 0, ((($page - 1) * $pagination->limit) > ($total - $pagination->limit)) ? $total : ((($page - 1) * $pagination->limit) + $pagination->limit), $total, ceil($total / $pagination->limit));
        $data['current_url'] = html_entity_decode($this->url->link($this->module_path . '/ocsociallogin/statistics', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true));
        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $active_tab['active'] = 5;
        $data['tab_common'] = $this->load->controller($this->module_path . '/ocsociallogin/tabs', $active_tab);


        if (VERSION < '2.2.0') {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/statistics.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/statistics', $data));
        }
    }

    public function support()
    {
        $store_id = 0;
        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        }

        $this->load->language($this->module_path . '/ocsociallogin');
        $this->document->setTitle($this->language->get('heading_title_main'));
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('tab_support');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->session_token_key . '=' . $this->session_token . "&store_id=" . $store_id, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token . "&store_id=" . $store_id . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->module_path . '/kbproductsizechart', $this->session_token_key . '=' . $this->session_token . "&store_id=" . $store_id, true)
        );
        $data['cancel'] = $this->url->link($this->extension_path, $this->session_token_key . '=' . $this->session_token . "&store_id=" . $store_id . '&type=module', true);
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['text_in_case_of_any_issue'] = $this->language->get('text_in_case_of_any_issue');
        $data['text_or'] = $this->language->get('text_or');
        $data['text_click_here'] = $this->language->get('text_click_here');
        $data['text_to_rise_the_ticket'] = $this->language->get('text_to_rise_the_ticket');



        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['active'] = 6;
        $data['store_id'] = $store_id;
        $data['tabs'] = $this->load->controller($this->module_path . '/ocsociallogin/tabs', $data);
        $data_swticher['current_url'] = $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token, true);
        $data_swticher['store_id'] = $store_id;
        $data['storeSwticher'] = $this->load->controller($this->module_path . '/ocsociallogin/storeSwticher', $data_swticher);
        if (VERSION < '2.2.0') {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/support.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view($this->module_path . '/ocsociallogin/support', $data));
        }
    }

    public function storeSwticher($data_switcher)
    {
        $this->load->language($this->module_path . '/ocsociallogin');
        $this->load->model('setting/store');
        $stores = $this->model_setting_store->getStores();
        if (!empty($stores)) {
            foreach ($stores as $result) {
                $data['all_store'][] = array(
                    'id' => $result['store_id'],
                    'name' => $result['name'],
                );
            }
            $data['current_url'] = $data_switcher['current_url'];
            $data['store_id'] = $data_switcher['store_id'];
            $data['default'] = $this->language->get('default');
            if (VERSION < '2.2.0') {
                return $this->load->view($this->module_path . '/ocsociallogin/storeswticher.tpl', $data);
            } else {
                return $this->load->view($this->module_path . '/ocsociallogin/storeswticher', $data);
            }
        }
    }

    public function tabs($active_tab)
    {
        $this->load->language($this->module_path . '/kbblocker');
        $store_id = 0;
        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        }
        $data['active'] = $active_tab['active'];
        $data['tab_general_setting'] = $this->language->get('tab_general_setting');
        $data['tab_configuration_accounts'] = $this->language->get('tab_accounts_settings');
        $data['tab_configuration_modules'] = $this->language->get('tab_configure_modules');
        $data['tab_configure_icons'] = $this->language->get('tab_configure_icons');
        $data['tab_statistics'] = $this->language->get('tab_statistics');
        $data['tab_support'] = $this->language->get('tab_support');

        $data['general_setting'] = $this->url->link($this->module_path . '/ocsociallogin', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
        $data['configuration_accounts'] = $this->url->link($this->module_path . '/ocsociallogin/configuration_accounts', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
        $data['configuration_modules'] = $this->url->link($this->module_path . '/ocsociallogin/configuration_modules', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
        $data['configure_icons'] = $this->url->link($this->module_path . '/ocsociallogin/configure_icons', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
        $data['statistics'] = $this->url->link($this->module_path . '/ocsociallogin/statistics', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
        $data['support_url'] = $this->url->link($this->module_path . '/ocsociallogin/support', $this->session_token_key . '=' . $this->session_token . "&store_id=" . $store_id, true);

        if (VERSION < '2.2.0') {
            return $this->load->view($this->module_path . '/ocsociallogin/tabs.tpl', $data);
        } else {
            return $this->load->view($this->module_path . '/ocsociallogin/tabs', $data);
        }
    }

    public function install()
    {

        $create_ocsociallogin_layout = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ocsociallogin_layout` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(30) DEFAULT NULL,
            `choose_login` text,
            `icon_type` varchar(30) DEFAULT NULL,
            `hover_effect` varchar(30) DEFAULT NULL,
            `button_color` varchar(30) DEFAULT NULL,
            `color` text,
            `alignment` varchar(30) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";

        $create_ocsociallogin_registerdetail = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ocsociallogin_users` (
            `user_id` int(11) NOT NULL AUTO_INCREMENT,
            `oc_customer_id` varchar(100) NOT NULL,
            `social_side_user_id` varchar(100) NOT NULL,
            `type` varchar(100) NOT NULL,
            `data_of_registration` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

        $create_ocsociallogin_logindetail = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ocsociallogin_login_history` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` varchar(100) NOT NULL,
            `type` varchar(100) NOT NULL,
            `user_name` varchar(100) NOT NULL,
            `date_of_login` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
             PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

        $this->db->query($create_ocsociallogin_layout);
        $this->db->query($create_ocsociallogin_registerdetail);
        $this->db->query($create_ocsociallogin_logindetail);
    }

}
