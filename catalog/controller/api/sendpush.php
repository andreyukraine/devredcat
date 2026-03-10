<?php

class ControllerApiSendpush extends Controller
{
    /**
     * API Endpoint to send push notification
     * URL: index.php?route=api/sendpush
     */
    public function index()
    {
        $json = [];

        $token = isset($this->request->post['token']) ? $this->request->post['token'] : '';
        $user_code = isset($this->request->post['user_code']) ? $this->request->post['user_code'] : '';
        $user_id = isset($this->request->post['user_id']) ? $this->request->post['user_id'] : '';

        $title = isset($this->request->post['title']) ? $this->request->post['title'] : 'Нове замовлення!';
        $message = isset($this->request->post['message']) ? $this->request->post['message'] : 'Це повідомлення через DATA';

        if (!$token && !$user_code && !$user_id) {
            $json['error'] = 'Token or user_code or user_id is required';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        if ($user_code) {
            $this->load->model('user/api');
            $user_info = $this->model_user_api->getUserByCodeErp($user_code);
            if ($user_info && isset($user_info['fcm_token'])) {
                $token = $user_info['fcm_token'];
            } else {
                $json['error'] = 'User token not found for user_code: ' . $user_code;
            }
        } elseif ($user_id) {
            $query = $this->db->query("SELECT fcm_token FROM `" . DB_PREFIX . "user` WHERE user_id = '" . (int)$user_id . "'");
            if ($query->num_rows && isset($query->row['fcm_token'])) {
                $token = $query->row['fcm_token'];
            } else {
                $json['error'] = 'User token not found for user_id: ' . $user_id;
            }
        }

        if ($token) {
            $data = [
                'title'       => $title,
                'message'     => $message,
                'OPEN_ORDERS' => 'true'
            ];

            $result = $this->send($token, $data);

            if (isset($result['error'])) {
                $json['error'] = $result['error'];
                $json['status'] = 0;
            } else {
                $json['success'] = 'Notification sent successfully';
                $json['response'] = json_decode($result['response'], true);
                $json['status'] = 1;
            }
        } elseif (!isset($json['error'])) {
            $json['error'] = 'No token found for the provided information';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Internal method to send push notification
     * 
     * @param string $token Device FCM token
     * @param array $data Data payload
     * @return array Result with response or error
     */
    public function send($token, $data = [])
    {
        require_once(DIR_SYSTEM . 'library/fcm.php');
        $fcm = new Fcm($this->registry);
        return $fcm->send($token, $data);
    }
}
