<?php
class ControllerExtensionDToolbarDAjaxFilterSEO extends Controller
{
    private $codename = 'd_ajax_filter_seo';
    private $route = 'extension/d_toolbar/d_ajax_filter_seo';
    private $config_file = 'd_ajax_filter_seo';
    private $error = array();
    
    /*
    *	Functions for Toolbar.
    */
    public function toolbar_config($route)
    {
        $this->load->model($this->route);
        $this->load->model('extension/module/' . $this->codename);
        
        $data = array();
        if ($route == 'product/category') {
            if (isset($this->request->get['path']) && isset($this->request->get['ajaxfilter'])) {
                $query_info = $this->{'model_extension_module_'.$this->codename}->getCurrentQuery();
                if (!empty($query_info)) {
                    $data['route'] = 'af_query_id=' . $query_info['query_id'];
                    if (VERSION >= '3.0.0.0') {
                        $data['edit'] = $this->{'model_extension_d_toolbar_' . $this->codename}->link('extension/d_ajax_filter_seo/query/edit', 'user_token=' . $this->session->data['user_token'] . '&query_id=' . $query_info['query_id'], true);
                    } else {
                        $data['edit'] = $this->{'model_extension_d_toolbar_' . $this->codename}->link('extension/d_ajax_filter_seo/query/edit', 'token=' . $this->session->data['token'] . '&query_id=' . $query_info['query_id'], true);
                    }
                }
            }
        }
        
        return $data;
    }
}
