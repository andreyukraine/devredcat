<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountDelivery extends Controller {
	private $error = array();

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/delivery', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/delivery');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_delivery'),
			'href' => $this->url->link('account/delivery', '', true)
		);

    $this->load->model('catalog/information');

    if ((int)$this->customer->getGroupId() == 17){
      $information_info = $this->model_catalog_information->getInformation(13);
      if ($information_info) {
        $data['desc'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');
      }
    } elseif ((int)$this->customer->getGroupId() != 17 && (int)$this->customer->getGroupId() != 21){
      $information_info = $this->model_catalog_information->getInformation(12);
      if ($information_info) {
        $data['desc'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');
      }
    }

		$data['action'] = $this->url->link('account/delivery', '', true);

		// Custom Fields
    $data['menu'] = $this->load->controller('account/menu');

		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/delivery', $data));
	}



}
