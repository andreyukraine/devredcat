<?php

class ControllerExtensionModuleSetsManage extends Controller {

    private $error = array();

    public function index() {
        $newline_symbols = array("\r", "\n");

        $this->load->language('extension/module/sets_manage');
        $this->load->model('catalog/category');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_info'] = $this->language->get('text_info');
        $data['text_before'] = $this->language->get('text_before');
        $data['text_after'] = $this->language->get('text_after');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['entry_category'] = $this->language->get('entry_category');
        $data['entry_products'] = $this->language->get('entry_products');

        $data['entry_name'] = $this->language->get('entry_name');
        $data['entry_discount'] = $this->language->get('entry_discount');
        $data['entry_qunatity'] = $this->language->get('entry_qunatity');

        $data['entry_option'] = $this->language->get('entry_option');
        $data['entry_delete'] = $this->language->get('entry_delete');

        $data['entry_category'] = $this->language->get('entry_category');
        $data['entry_category'] = $this->language->get('entry_category');
        $data['entry_category'] = $this->language->get('entry_category');

        $data['btn_add_set'] = $this->language->get('btn_add_set');
        $data['btn_del_set'] = $this->language->get('btn_del_set');


        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

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
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'].'&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/sets', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/sets', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'].'&type=module', true);

        if (floatval(VERSION) >= 2.2)
            $data['cats'] = $this->model_catalog_category->getCategories();
        else
            $data['cats'] = $this->model_catalog_category->getCategories(array());
        
        $data['user_token'] = $this->session->data['user_token'];
        if (floatval(VERSION) >= 2.2)
            $data['row'] = str_replace($newline_symbols, "", $this->load->view('extension/module/sets/sets_manage_row'));
        else
            $data['row'] = str_replace($newline_symbols, "", $this->load->view('extension/module/sets/sets_manage_row.tpl'));
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        if (floatval(VERSION) >= 2.2)
            $this->response->setOutput($this->load->view('extension/module/sets/sets_manage', $data));
        else
            $this->response->setOutput($this->load->view('extension/module/sets/sets_manage.tpl', $data));
    }

    public function add() {
        $json = array();
        $this->load->language('extension/module/sets_manage');

        $this->load->model('catalog/product');
        if (isset($this->request->post['cat']) && isset($this->request->post['products']) && count($this->request->post['products'])) {
            $cat = $this->request->post['cat'];
            $products = $this->model_catalog_product->getProductsByCategoryId($cat);
            foreach ($products as $product) {
                $current_prd = array();



                $current_prd["product_name"] = $product['name'];
                $current_prd["product_id"] = $product['product_id'];
                $current_prd["quantity"] = 1;
                $current_prd["option"] = array();
                $current_prd["use_option"] = 'no';

                $old_summ = 0;
                $new_summ = 0;

                $set['products'] = $this->request->post['products'];
                array_unshift($set['products'], $current_prd);

                $set['discount'] = $this->request->post['discount'];

                if (!empty($product['sets']))
                    $sets = json_decode($product['sets'], true);
                else
                    $sets = array();

                $sets[] = $set;

                $sets_json = json_encode($sets, JSON_UNESCAPED_UNICODE);

                $this->db->query("UPDATE " . DB_PREFIX . "product SET sets = '" . $sets_json . "' WHERE product_id = '" . (int) $product['product_id'] . "'");
            }
            $json['success'] = $this->language->get('entry_success');
        }
        else {
            $json['error'] = $this->language->get('error_empty_products');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function clear() {
        $json = array();
        $this->load->language('extension/module/sets_manage');

        $this->load->model('catalog/product');
        if (isset($this->request->post['cat_id'])) {
            $cat = $this->request->post['cat_id'];
            $products = $this->model_catalog_product->getProductsByCategoryId($cat);
            foreach ($products as $product) {
                $this->db->query("UPDATE " . DB_PREFIX . "product SET sets = '' WHERE product_id = '" . (int) $product['product_id'] . "'");
            }
            $json['success'] = $this->language->get('entry_success');
        } else {
            $json['error'] = $this->language->get('error_empty_category');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/sets_manage')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }


        return !$this->error;
    }

}

?>