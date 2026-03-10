<?php
class ControllerCustomerPushNotification extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('customer/push_notification');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('user/user');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->send();
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('customer/push_notification', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('customer/push_notification', 'user_token=' . $this->session->data['user_token'], true);

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

		if (isset($this->error['message'])) {
			$data['error_message'] = $this->error['message'];
		} else {
			$data['error_message'] = '';
		}

		if (isset($this->error['user'])) {
			$data['error_user'] = $this->error['user'];
		} else {
			$data['error_user'] = '';
		}

		$data['users'] = array();
		$results = $this->model_user_user->getUsers();
		
		foreach ($results as $result) {
			if ($result['fcm_token'] && $result['status']) {
				$data['users'][] = array(
					'user_id' => $result['user_id'],
					'name'    => $result['firstname'] . ' ' . $result['lastname'] . ' (' . $result['username'] . ')'
				);
			}
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		if (isset($this->request->post['message'])) {
			$data['message'] = $this->request->post['message'];
		} else {
			$data['message'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('customer/push_notification', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'customer/push_notification')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['selected'])) {
			$this->error['user'] = $this->language->get('error_user');
		}

		if ((utf8_strlen($this->request->post['message']) < 1) || (utf8_strlen($this->request->post['message']) > 1000)) {
			$this->error['message'] = $this->language->get('error_message');
		}

		return !$this->error;
	}

	protected function send() {
		$selected_users = $this->request->post['selected'];
		$message_text = $this->request->post['message'];

		require_once(DIR_SYSTEM . 'library/fcm.php');
		$fcm = new Fcm($this->registry);

		$sent_count = 0;
		
		foreach ($selected_users as $user_id) {
			$user_query = $this->db->query("SELECT user_id, username, fcm_token FROM `" . DB_PREFIX . "user` WHERE user_id = '" . (int)$user_id . "' AND fcm_token != '' AND status = '1'");

			if ($user_query->num_rows) {
				$user = $user_query->row;
				
				$push_data = [
					'title'       => 'Повідомлення від адміністратора',
					'message'     => $message_text,
					'OPEN_ORDERS' => 'true'
				];

				$result = $fcm->send($user['fcm_token'], $push_data);

				if (!isset($result['error'])) {
					$sent_count++;
				} else {
					$this->log->write("FCM PUSH ERROR: Failed to send to user " . $user['username'] . " (ID: " . $user['user_id'] . "). Error: " . $result['error']);
				}
			}
		}

		if ($sent_count > 0) {
			$this->session->data['success'] = sprintf($this->language->get('text_success'), $sent_count);
		} else {
			$this->error['warning'] = 'Не вдалося надіслати push повідомлення жодному користувачу.';
		}
	}
}
