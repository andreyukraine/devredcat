<?php

class Controllerextensionmoduleallsets extends Controller {

    public function index() {
        if (!$this->config->get('module_sets_status'))
            return;

        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $this->load->language('extension/module/all_sets');

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        if (isset($this->request->get['limit']))
            $limit = (int) $this->request->get['limit'];
        else
            $limit = $this->config->get($this->config->get('config_theme') . '_product_limit');
        if (!$limit)
            $limit = 10;

        $start = ($page - 1) * $limit;

        $data['text_sets'] = $this->language->get('text_sets');
        $data['text_buy_sets'] = $this->language->get('text_buy_sets');
        $data['text_economy'] = $this->language->get('text_economy');

        $sets = false;

        $total_sql = $this->db->query("SELECT COUNT(`sets`) as total FROM `" . DB_PREFIX . "product` WHERE sets <>''");
        $query = $this->db->query("SELECT `product_id`,`sets` FROM `" . DB_PREFIX . "product` WHERE sets <>'' LIMIT $start,$limit");
        $all_sets = array();
        if ($query->num_rows) {
            foreach ($query->rows as $s) {
                $sets = $this->load->controller('extension/module/sets/getSet', $s['product_id']);
                if ($sets)
                    $all_sets[] = $sets;
            }
        }

        $data['sets_array'] = $all_sets;

        if ($data['sets_array']) {
            $this->document->addScript('catalog/view/javascript/sets/script.js');

            $this->document->addScript('catalog/view/javascript/slick/slick.min.js');
            $this->document->addStyle('catalog/view/javascript/slick/slick.css');
            $this->document->addStyle('catalog/view/javascript/slick/slick-theme.css');

            $this->document->addStyle('catalog/view/javascript/sets/style.css');
        }

        $pagination = new Pagination();
        $pagination->total = $total_sql->row['total'];
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('extension/module/all_sets', 'page={page}');

        $data['pagination'] = $pagination->render();
        $data['decimal_place'] = $this->currency->getDecimalPlace($this->session->data['currency']);
        $data['show_disc_prec'] = $this->config->get('module_sets_show_disc_prec');
        $data['show_old_price'] = $this->config->get('module_sets_show_old_price');
        $data['show_qty'] = $this->config->get('module_sets_show_qty');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $this->response->setOutput($this->load->view('extension/module/all_sets', $data));
    }

}
