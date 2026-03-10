<?php
class ControllerExtensionModuleBanner extends Controller {
	public function index($setting) {
		static $module = 0;

		$this->load->model('design/banner');
		$this->load->model('tool/image');

    $is_mobile = 0;
    if ($this->mobile_detect->isMobile()) {
      $is_mobile = 1;
    }

		$data['banners'] = array();
		$results = array();

		//++Andrey
		$data['type_slider'] = "";
		if (isset($this->request->get['path'])) {
      // Это страница категории
      $parts = explode('_', (string)$this->request->get['path']);
      if (isset($parts[2])) {
        $category_id = $parts[2];
      }elseif (isset($parts[1])){
        $category_id = $parts[1];
      }else{
        $category_id = $parts[0];
      }
      $data['type_slider'] = "catalog";
      $results = $this->model_design_banner->getSlideshowCategory($category_id);
    } elseif (isset($this->request->get['manufacturer_id'])) {
      // Это страница производителя
      $manufacture_id = $this->request->get['manufacturer_id'];
      $data['type_slider'] = "manufacture";
      $results = $this->model_design_banner->getSlideshowManufacture($manufacture_id);
    }

		foreach ($results as $result) {

      if (empty($result['video'])){
        if (is_file(DIR_IMAGE . $result['image'])) {

          if ($is_mobile > 0){
            $image = $this->model_tool_image->resize($result['image_mob'], 500, 400);
          }else{
            $image = $this->model_tool_image->resize($result['image'], 3000, 400);
          }
          $data['banners'][] = array(
            'title' => $result['title'],
            'link' => $result['link'],
            'image' =>$image,
            'type' => 0
          );
        }
			}else{
        $data['banners'][] = array(
          'title' => $result['title'],
          'link' => $result['link'],
          'image' => "",
          'type' => 1,
          'video' => $result['video']
        );
      }

		}

		return $this->load->view('extension/module/banner', $data);
	}
}
