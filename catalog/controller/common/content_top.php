<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerCommonContentTop extends Controller {
	public function index() {
		$this->load->model('design/layout');

		if (isset($this->request->get['route'])) {
			$route = (string)$this->request->get['route'];
		} else {
			$route = 'common/home';
		}

		$layout_id = 0;

		if ($route == 'product/category' && isset($this->request->get['path'])) {
			$this->load->model('catalog/category');

			$path = explode('_', (string)$this->request->get['path']);

			$layout_id = $this->model_catalog_category->getCategoryLayoutId(end($path));
		}
		
		if ($route == 'product/manufacturer/info' && isset($this->request->get['manufacturer_id'])) {
			$this->load->model('catalog/manufacturer');
		
			$layout_id = $this->model_catalog_manufacturer->getManufacturerLayoutId($this->request->get['manufacturer_id']);
		}

		if ($route == 'product/product' && isset($this->request->get['product_id'])) {
			$this->load->model('catalog/product');

			$layout_id = $this->model_catalog_product->getProductLayoutId($this->request->get['product_id']);
		}

		if ($route == 'information/information' && isset($this->request->get['information_id'])) {
			$this->load->model('catalog/information');

			$layout_id = $this->model_catalog_information->getInformationLayoutId($this->request->get['information_id']);
		}

		if (!$layout_id) {
			$layout_id = $this->model_design_layout->getLayout($route);
		}

		if (!$layout_id) {
			$layout_id = $this->config->get('config_layout_id');
		}

		$this->load->model('setting/module');

		$data['modules'] = array();

    $modules_bottom = $this->model_design_layout->getLayoutModules($layout_id, 'content_bottom');
    $count_bottom = count($modules_bottom);

		$modules = $this->model_design_layout->getLayoutModules($layout_id, 'content_top');

    foreach ($modules as $key => $module) {
      $part = explode('.', $module['code']);
      $z_index = $count_bottom + count($modules) - $key;

      if (isset($part[0]) && $this->config->get('module_' . $part[0] . '_status')) {
        $module_data = $this->load->controller('extension/module/' . $part[0]);

        if ($module_data) {
          // Добавляем стиль с z-index к модулю
          $module_data = '<div class="'.$part[0].'" style="position: relative; z-index: ' . $z_index . ';">' . $module_data . '</div>';
          if (isset($part[1])) {
            $data['modules'][$part[1]] = $module_data;
          }else{
            $data['modules'][$module['code']] = $module_data;
          }
        }
      }

      if (isset($part[1])) {
        $setting_info = $this->model_setting_module->getModule($part[1]);

        if ($setting_info && $setting_info['status']) {
          $output = $this->load->controller('extension/module/' . $part[0], $setting_info);

          if ($output) {
            $output = '<div class="' . $part[0] . '" style="position: relative; z-index: ' . $z_index . ';">' . $output . '</div>';
            $data['modules'][$part[1]] = $output;
          }
        }
      }
    }

		return $this->load->view('common/content_top', $data);
	}
}
