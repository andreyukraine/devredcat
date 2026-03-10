<?php

class ControllerExtensionModuleOcimagecrop extends Controller
{
    private $error = array();
    private $status_file = DIR . 'crons/cron_status.txt';

    public function index()
    {
        $this->load->language('extension/module/ocimagecrop');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_ocimagecrop', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/ocimagecrop', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/ocimagecrop', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['module_ocimagecrop_status'])) {
            $data['module_ocimagecrop_status'] = $this->request->post['module_ocimagecrop_status'];
        } else {
            $data['module_ocimagecrop_status'] = $this->config->get('module_ocimagecrop_status');
        }

        // Categories for the filter
        $this->load->model('catalog/category');
        $categories = $this->model_catalog_category->getAllCategories();
        $data['categories'] = array();

        foreach ($categories as $category) {
            $category_info = $this->model_catalog_category->getCategory($category['category_id']);
            if ($category_info) {
                $data['categories'][] = array(
                    'category_id' => $category_info['category_id'],
                    'name'        => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
                );
            }
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['is_admin'] = $this->user->getGroupId();

        $this->response->setOutput($this->load->view('extension/module/ocimagecrop', $data));
    }

    public function crop()
    {
        $json = array();

        if (!$this->user->hasPermission('modify', 'extension/module/ocimagecrop')) {
            $json['error'] = 'У вас немає прав для виконання цієї операції!';
        } else {
            $json['status'] = 'Процес виконується.';
            file_put_contents($this->status_file, "started");

            $this->load->model('extension/module/ocimport');

            $img_name = "";
            if (isset($this->request->get['img_name'])) {
                $img_name = $this->request->get['img_name'];
            }

            $filter_data = array();
            if (isset($this->request->get['img_category'])) {
                $filter_data = array(
                    'filter_category' => $this->request->get['img_category']
                );
            }

            $this->model_extension_module_ocimport->cropProductImg($filter_data, $img_name);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function checkStatus()
    {
        $json = array();

        if (file_exists($this->status_file)) {
            $lines = file($this->status_file, FILE_IGNORE_NEW_LINES);
            $status = array_shift($lines);

            switch ($status) {
                case "started":
                    $json['status'] = 'Задача все ще виконується...';
                    break;
                case "load_products":
                    $json['status'] = 'Завантажуємо товари...';
                    break;
                case "completed":
                    $json['success'] = 'Задачу завершено!';
                    break;
                case "error":
                    $json['error'] = $lines;
                    break;
                default:
                    $json['status'] = 'Процес виконується.';
                    break;
            }

            if (!empty($lines)) {
                $json['progress'] = implode("\n", $lines);
            }
        } else {
            $json['error'] = 'Файл статусу не знайдено.';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/ocimagecrop')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
