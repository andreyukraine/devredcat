<?php

class ControllerInformationMap extends Controller {
	public function index() {

    $data = array();

		$this->load->language('information/map');
    $this->load->model('extension/module/ocwarehouses');
    $warehouses = $this->model_extension_module_ocwarehouses->getWarehouseList();

    $warehouses_list = array();
    if (!empty($this->config->get('config_lat')) && !empty($this->config->get('config_lon'))){
      $warehouses_list[] = array(
        'warehouse_id' => 1,
        'uid' => '1',
        'name' => $this->config->get('config_name'),
        'phone' => $this->config->get('config_telephone'),
        'address' => nl2br($this->config->get('config_address')),
        'lat' => nl2br($this->config->get('config_lat')),
        'lon' => nl2br($this->config->get('config_lon')),
        'working_hours' => nl2br(html_entity_decode($this->config->get('config_open'))),
        'status' => ''
      );
    }else {
      foreach ($warehouses as $result) {
        $warehouses_list[] = array(
          'warehouse_id' => $result['warehouse_id'],
          'uid' => $result['uid'],
          'name' => $result['name'],
          'phone' => $result['phone'],
          'address' => nl2br($result['address']),
          'lat' => nl2br($result['lat']),
          'lon' => nl2br($result['lon']),
          'working_hours' => nl2br(html_entity_decode($result['working_hours'])),
          'status' => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'))
        );
      }
    }

    $data['warehouses'] = json_encode($warehouses_list);
    $data['text_working_hours'] = $this->language->get("text_working_hours");
    $data['text_view_map'] = $this->language->get("text_view_map");

    return $this->load->view('information/map', $data);
	}
}
