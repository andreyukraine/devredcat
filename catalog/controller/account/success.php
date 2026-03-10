<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountSuccess extends Controller {
	public function index() {
		$this->load->language('account/success');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

    $data['text_message_title'] = $this->language->get('text_message_title');
    $data['account'] = $this->url->link('account/account', '', true);

    $data['is_proof'] = true;
		if ($this->customer->isLogged()) {
			$data['text_message'] = sprintf($this->language->get('text_message'), $this->url->link('information/contact'));
    } else {
      $data['is_proof'] = false;
			$data['text_message'] = sprintf($this->language->get('text_approval'), $this->config->get('config_name'), $this->url->link('information/contact'));
		}



		if ($this->cart->hasProducts()) {
			$data['continue'] = $this->url->link('checkout/cart');
		} else {
			$data['continue'] = $this->url->link('account/account', '', true);
		}

    //режим каталогу
    if ($this->config->get('config_is_catalog')) {
      $data['config_is_catalog'] = $this->config->get('config_is_catalog');
    }

    $this->load->model('tool/image');
    $img_bg = "account_registr_bg.jpg";

    if (file_exists(DIR_IMAGE . $img_bg)) {
      $data["image"] = $this->model_tool_image->resize($img_bg, 1000, 1000);
    } else {
      $data["image"] = $this->model_tool_image->resize("no_image.png", 1000, 1000);
    }

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/success', $data));
	}
}
