<?php
class ControllerMarketplaceEvent extends Controller {
	private $error = array();
	
	public function index() {
		$this->load->language('marketplace/event');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/event');

    $this->getList();
	}

	public function enable() {
		$this->load->language('marketplace/event');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/event');

		if (isset($this->request->get['event_id']) && $this->validate()) {
			$this->model_setting_event->enableEvent($this->request->get['event_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function disable() {
		$this->load->language('marketplace/event');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/event');

		if (isset($this->request->get['event_id']) && $this->validate()) {
			$this->model_setting_event->disableEvent($this->request->get['event_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}
	
	public function delete() {
		$this->load->language('marketplace/event');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/event');

		if (isset($this->request->post['selected']) && $this->validate()) {
			foreach ($this->request->post['selected'] as $event_id) {
				$this->model_setting_event->deleteEvent($event_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}	
	
	public function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'code';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

    $data['add'] = $this->url->link('marketplace/event/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
    $data['delete'] = $this->url->link('marketplace/event/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['events'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$event_total = $this->model_setting_event->getTotalEvents();

		$results = $this->model_setting_event->getEvents($filter_data);

		foreach ($results as $result) {
			$data['events'][] = array(
				'event_id'   => $result['event_id'],
				'code'       => $result['code'],
				'trigger'    => $result['trigger'],
				'action'     => $result['action'],
				'sort_order' => $result['sort_order'],
				'status'     => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
        'edit'       => $this->url->link('marketplace/event/edit', 'user_token=' . $this->session->data['user_token'] . '&event_id=' . $result['event_id'] . $url, true),
				'enable'     => $this->url->link('marketplace/event/enable', 'user_token=' . $this->session->data['user_token'] . '&event_id=' . $result['event_id'] . $url, true),
				'disable'    => $this->url->link('marketplace/event/disable', 'user_token=' . $this->session->data['user_token'] . '&event_id=' . $result['event_id'] . $url, true),
				'enabled'    => $result['status']
			);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_code'] = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . '&sort=code' . $url, true);
		$data['sort_sort_order'] = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url, true);
		$data['sort_status'] = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $event_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($event_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($event_total - $this->config->get('config_limit_admin'))) ? $event_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $event_total, ceil($event_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('marketplace/event', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'marketplace/event')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

  public function add() {
    $this->load->language('marketplace/event');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/event');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

      $code = $this->request->post['code'];
      $trigger = $this->request->post['trigger'];
      $action = $this->request->post['action'];
      $status = (int)$this->request->post['status'];
      $sort_order = (int)$this->request->post['sort_order'];

      $this->model_setting_event->addEvent($code,$trigger,$action,$status,$sort_order);

      $this->session->data['success'] = $this->language->get('text_success');

      $url = '';

      if (isset($this->request->get['sort'])) {
        $url .= '&sort=' . $this->request->get['sort'];
      }

      if (isset($this->request->get['order'])) {
        $url .= '&order=' . $this->request->get['order'];
      }

      if (isset($this->request->get['page'])) {
        $url .= '&page=' . $this->request->get['page'];
      }

      $this->response->redirect($this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }

    $this->getForm();
  }

  public function edit() {
    $this->load->language('marketplace/event');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/event');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') &&$this->validate()) {

      $event_id = (int)$this->request->get['event_id'];
      $code = $this->request->post['code'];
      $trigger = $this->request->post['trigger'];
      $action = $this->request->post['action'];
      $status = (int)$this->request->post['status'];
      $sort_order = (int)$this->request->post['sort_order'];

      $this->model_setting_event->editEvent($event_id,$code,$trigger,$action,$status,$sort_order);

      $this->session->data['success'] = $this->language->get('text_success');

      $url = '';

      if (isset($this->request->get['sort'])) {
        $url .= '&sort=' . $this->request->get['sort'];
      }

      if (isset($this->request->get['order'])) {
        $url .= '&order=' . $this->request->get['order'];
      }

      if (isset($this->request->get['page'])) {
        $url .= '&page=' . $this->request->get['page'];
      }

      $this->response->redirect($this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }

    $this->getForm();
  }

  protected function getForm() {

    $url = '';

    if (isset($this->request->get['sort'])) {
      $url .= '&sort=' . $this->request->get['sort'];
    }

    if (isset($this->request->get['order'])) {
      $url .= '&order=' . $this->request->get['order'];
    }

    if (isset($this->request->get['page'])) {
      $url .= '&page=' . $this->request->get['page'];
    }

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true)
    );

    if (!isset($this->request->get['event_id'])) {
      $data['action_form'] = $this->url->link('marketplace/event/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
    } else {
      $data['action_form'] = $this->url->link('marketplace/event/edit', 'user_token=' . $this->session->data['user_token'] . '&event_id=' . $this->request->get['event_id'] . $url, true);
    }

    $data['cancel'] = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'] . $url, true);

    $this->load->model('setting/event');

    if (isset($this->request->get['event_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
      $event_info = $this->model_setting_event->getEvent($this->request->get['event_id']);
    }

    $data['user_token'] = $this->session->data['user_token'];

    if (isset($this->request->post['event_id'])) {
      $data['event_id'] = $this->request->post['event_id'];
    } elseif (!empty($event_info)) {
      $data['event_id'] = $event_info['event_id'];
    } else {
      $data['event_id'] = 0;
    }

    if (isset($this->request->post['code'])) {
      $data['code'] = $this->request->post['code'];
    } elseif (!empty($event_info)) {
      $data['code'] = $event_info['code'];
    } else {
      $data['code'] = "";
    }

    if (isset($this->request->post['trigger'])) {
      $data['trigger'] = $this->request->post['trigger'];
    } elseif (!empty($event_info)) {
      $data['trigger'] = $event_info['trigger'];
    } else {
      $data['trigger'] = "";
    }

    if (isset($this->request->post['action'])) {
      $data['action'] = $this->request->post['action'];
    } elseif (!empty($event_info)) {
      $data['action'] = $event_info['action'];
    } else {
      $data['action'] = "";
    }

    if (isset($this->request->post['status'])) {
      $data['status'] = $this->request->post['status'];
    } elseif (!empty($event_info)) {
      $data['status'] = $event_info['status'];
    } else {
      $data['status'] = "";
    }

    if (isset($this->request->post['sort_order'])) {
      $data['sort_order'] = $this->request->post['sort_order'];
    } elseif (!empty($event_info)) {
      $data['sort_order'] = $event_info['sort_order'];
    } else {
      $data['sort_order'] = "";
    }

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('marketplace/event_form', $data));
  }
}
