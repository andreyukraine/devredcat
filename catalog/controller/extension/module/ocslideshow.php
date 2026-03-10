<?php
class ControllerExtensionModuleOcslideshow extends Controller {
	public function index($setting) { 
		static $module = 0;
		$this->language->load('extension/module/ocslideshow');
		$this->load->model('ocslideshow/slide');
		$this->load->model('tool/image');		

		$data = array();
		$data['text_readmore'] = $this->language->get('text_readmore');
		$data['ocslideshows'] = array();
		$data['animate'] = 'animate-in';
		$results = array();

		if(isset($setting['banner'])) {
			$results = $this->model_ocslideshow_slide->getocslideshow($setting['banner']);
		}
		if($results ) {
			$store_id  = $this->config->get('config_store_id');
      $slider_id = (int)$setting['banner'];
      $data['slider_id'] = $slider_id;

			foreach ($results as $result) {
			//	if (file_exists(DIR_IMAGE . $result['image'])) {
			  $banner_store = array();
			  if(isset($result['banner_store'])) {
				$banner_store = explode(',',$result['banner_store']);
			  }

        list($width_orig, $height_orig) = getimagesize(DIR_IMAGE . $result['image']);
        $height = ($setting['width'] / $width_orig) * $height_orig;


			  if(in_array($store_id,$banner_store)) {
					$data['ocslideshows'][] = array(
            'ocslideshow_id' => $result['ocslideshow_id'],
            'type_slide' => $result['type_slide'],
            'video' => $result['video'],
						'title' => $result['title'],
            'title_size' => $result['title_size'],
            'title_color' => $result['title_color'],
						'sub_title' => $result['sub_title'],
            'sub_size' => $result['sub_size'],
            'sub_title_color' => $result['sub_title_color'],
						'description' => html_entity_decode($result['description']),
						'link'  => $result['link'],
						'type'  => $result['type'],
						'image' => $this->model_tool_image->resize($result['image'], $setting['width'], $height),
						'small_image' => $this->config->get('config_url'). 'image/' . $result['small_image'],
						'small_image_name' => $result['small_image']
					);
			 }
					
				//}
				
				$data['slide_setting'] = $this->model_ocslideshow_slide->getSettingSlide($result['ocslideshow_id']);
//				 echo "<pre>";
//				  print_r($data['slide_setting']);
//				 echo "</pre>";
			}
			return $this->load->view('extension/module/ocslideshow', $data);

		}
	}
}
