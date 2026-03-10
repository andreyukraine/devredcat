<?php
class ControllerErrorNotFound extends Controller {
	public function index() {

		$this->load->language('error/not_found');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['route'])) {
			$url_data = $this->request->get;

			unset($url_data['_route_']);

			$route = $url_data['route'];

			unset($url_data['route']);

			$url = '';

			if ($url_data) {
				$url = '&' . urldecode(http_build_query($url_data, '', '&'));
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link($route, $url, $this->request->server['HTTPS'])
			);
		}

    $this->load->model('tool/image');
    $img_bg = "not-found.png";
    if (file_exists(DIR_IMAGE . $img_bg)) {
      $data["image"] = $this->model_tool_image->resize($img_bg, 350, 350);
    } else {
      $data["image"] = $this->model_tool_image->resize("no_image.png", 350, 350);
    }

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

		$this->response->setOutput($this->load->view('error/not_found', $data));
	}
}
