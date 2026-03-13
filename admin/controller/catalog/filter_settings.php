<?php
class ControllerCatalogFilterSettings extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('catalog/filter_settings');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/filter_settings');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_catalog_filter_settings->saveSettings($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('catalog/filter_settings', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['column_type'] = $this->language->get('column_type');
        $data['column_name'] = $this->language->get('column_name');
        $data['column_group'] = $this->language->get('column_group');
        $data['column_show'] = $this->language->get('column_show');
        $data['column_sort_order'] = $this->language->get('column_sort_order');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/filter_settings', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('catalog/filter_settings', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        $filter_groups = $this->model_catalog_filter_settings->getFilterGroups();
        $settings = $this->model_catalog_filter_settings->getSettings();

        $data['filter_groups'] = array();

        foreach ($filter_groups as $group) {
            $id = $group['id'];
            $show = isset($settings['filter_settings_show'][$id]) ? (int)$settings['filter_settings_show'][$id] : 0;
            $sort_order = isset($settings['filter_settings_sort_order'][$id]) ? (int)$settings['filter_settings_sort_order'][$id] : 0;

            $data['filter_groups'][] = array(
                'id'         => $id,
                'type'       => $group['type'],
                'name'       => $group['name'],
                'group'      => $group['group'],
                'show'       => $show,
                'sort_order' => $sort_order
            );
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/filter_settings', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'catalog/filter_settings')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
