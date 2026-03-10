<?php
class ControllerApiFcmtoken extends Controller {
  public function index() {

    $json = array();

    $this->load->model('user/api');

    $b = $this->request->post;
    if (isset($b['user_code']) && isset($b['fcm_token'])){
      $user_db = $this->model_user_api->getUserByCodeErp($b['user_code']);
      if (!empty($user_db)){
        $this->model_user_api->serUserFcmtoken($user_db['user_id'], $b['fcm_token']);
        $json['code'] = 200;
        $json['status'] = "Save fcm_token";
      }else{
        $json['code'] = 201;
        $json['status'] = "error find user bu code_erp";
      }
    }else{
      $json['code'] = 201;
      $json['status'] = "error key for request";
    }

    $this->response->setOutput(json_encode($json));
  }
}
