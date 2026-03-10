<?php

class ControllerExtensionModulePopups extends Controller {

    private $version = '1.0';
    private $error = array();
    private $token_var;
    private $extension_var;
    private $prefix;

    public function __construct($registry) {
        parent::__construct($registry);
        $this->token_var = (version_compare(VERSION, '3.0', '>=')) ? 'user_token' : 'token';
        $this->extension_var = (version_compare(VERSION, '3.0', '>=')) ? 'marketplace' : 'extension';
        $this->prefix = (version_compare(VERSION, '3.0', '>=')) ? 'module_' : '';
        $this->load->model('extension/module/popups');
        $data = $this->load->language('extension/module/popups');

    }

    public function install() {
        $this->model_extension_module_popups->install();
    }

    public function uninstall() {

    }

    public function index() {

        $data['heading_title'] = $this->language->get('heading_title');
        $this->document->setTitle($data['heading_title']);

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting($this->prefix . 'popups', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            if (isset($this->request->post['apply'])) {
                $this->response->redirect($this->url->link('extension/module/popups', $this->token_var . '=' . $this->session->data[$this->token_var], true));
            } else {
                $this->response->redirect($this->url->link($this->extension_var . '/extension', $this->token_var . '=' . $this->session->data[$this->token_var] . '&type=module', true));
            }
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->token_var . '=' . $this->session->data[$this->token_var], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($this->extension_var . '/extension', $this->token_var . '=' . $this->session->data[$this->token_var] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $data['heading_title'],
            'href' => $this->url->link('extension/module/popups', $this->token_var . '=' . $this->session->data[$this->token_var], true)
        );


        $data['prefix'] = $this->prefix;
        $data['token_var'] = $this->token_var;
        $data[$this->token_var] = $this->session->data[$this->token_var];
        $data['action'] = $this->url->link('extension/module/popups/save', $this->token_var . '=' . $this->session->data[$this->token_var], true);
        $data['cancel'] = $this->url->link($this->extension_var . '/extension', $this->token_var . '=' . $this->session->data[$this->token_var] . '&type=module', true);
        $data['text_info'] = sprintf($this->language->get('text_info'), $this->version);

        if (isset($this->request->post[$this->prefix . 'popups_status'])) {
            $data[$this->prefix . 'popups_status'] = $this->request->post[$this->prefix . 'popups_status'];
        } else {
            $data[$this->prefix . 'popups_status'] = $this->config->get($this->prefix . 'popups_status');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');


        $items  = $this->model_extension_module_popups->getAll();
        foreach ($items as $item){
          $data['items'][] = array(
            'id' => $item['id'],
            'status' => $item['status'],
            'title' => $item['title'],
            'date_start' => $item['date_start'],
            'date_end' => $item['date_end'],
          );
        }

        $data['edit_link'] = $this->url->link('extension/module/popups/edit', $this->token_var . '=' . $this->session->data[$this->token_var], true);
        $data['delete_link'] = $this->url->link('extension/module/popups/delete', $this->token_var . '=' . $this->session->data[$this->token_var], true);
        $data['l_title'] = $this->language->get('title');
        $data['l_text'] = $this->language->get('text');
        $data['l_date_start'] = $this->language->get('date_start');
        $data['l_date_end'] = $this->language->get('date_end');
        $this->response->setOutput($this->load->view('extension/module/popups', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/popups')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }


        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }

    public function edit()
    {
        if(!empty($this->request->get['id']))
        {
            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', $this->token_var . '=' . $this->session->data[$this->token_var], true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link($this->extension_var . '/extension', $this->token_var . '=' . $this->session->data[$this->token_var] . '&type=module', true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/popups', $this->token_var . '=' . $this->session->data[$this->token_var], true)
            );

            $id = (int)$this->request->get['id'];
            $data['id'] = $id;
            $data['item'] = $this->model_extension_module_popups->getOne($id);
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            $data['action'] = $this->url->link('extension/module/popups/save', $this->token_var . '=' . $this->session->data[$this->token_var], true);
            $data['text_info'] = sprintf($this->language->get('text_info'), $this->version);
            $data['l_title'] = $this->language->get('title');
            $data['l_text'] = $this->language->get('text');
            $data['l_date_start'] = $this->language->get('date_start');
            $data['l_date_end'] = $this->language->get('date_end');
            $data['success_text'] = $this->language->get('success_text');
            $data['heading_title'] = $this->language->get('heading_edit_title') . $data['item']['title'];
            $data['save'] = $this->language->get('Сохранить');
            $data['cancel'] = $this->url->link('extension/module/popups', $this->token_var . '=' . $this->session->data[$this->token_var], true);
            if(!empty($this->request->get['success']))
            {
                $data['success'] = 1;
            }
            if(!empty($this->request->get['error']))
            {
                $data['error'] = 1;
            }
            $this->response->setOutput($this->load->view('extension/module/popups_edit', $data));


        }
    }

    public function save()
    {
        if(!empty($this->request->post['id']))
        {
            $id = (int)$this->request->post['id'];
            $res = $this->model_extension_module_popups->save();
            if($res > 0)
            {
                $redirect_url = $this->url->link('extension/module/popups/edit', $this->token_var . '=' . $this->session->data[$this->token_var].'&success=1&id='.$id, true);
                $this->response->redirect($redirect_url);
            } else {
                $redirect_url = $this->url->link('extension/module/popups/edit', $this->token_var . '=' . $this->session->data[$this->token_var].'&error=1&id='.$id, true);
                $this->response->redirect($redirect_url);

            }
        } else {
            $res = $this->model_extension_module_popups->add();
            $redirect_url = $this->url->link('extension/module/popups', $this->token_var . '=' . $this->session->data[$this->token_var].'&success=1', true);
                $this->response->redirect($redirect_url);
        }
    }


    public function delete()
    {
        if(!empty($this->request->get['id']))
        {
            $id = (int)$this->request->get['id'];
            $this->model_extension_module_popups->delete($id);
        }
         $redirect_url = $this->url->link('extension/module/popups', $this->token_var . '=' . $this->session->data[$this->token_var], true);
         $this->response->redirect($redirect_url);

    }
}
