<?php

class ControllerExtensionModuleOcnotifyproduct extends Controller
{
  public function index()
  {
    $json["msg"] = "";
    $json["error"] = "";

    if (!$this->customer->isLogged()) {
      $json["error"] = 'Щоби ми могли повідомити Вас про надходження товару <span onclick="ocajaxlogin.appendLoginForm();">увійдіть в особистий кабінет</span>';
    }else {

      $this->load->model('extension/module/ocnotifyproduct');

      $data = array(
        "product_id" => $this->request->post["product_id"],
        "options" => $this->request->post["opt"],
      );

      $notyfy = $this->model_extension_module_ocnotifyproduct->getRequest($this->customer->getId(), $data);
      if (!empty($notyfy)){
        $json["msg"] = "Ви вже підписані ! Лист прийде, коли товар з'явиться";
      }else{
        $this->model_extension_module_ocnotifyproduct->addRequest($this->customer->getId(), $data);
        $json["msg"] = "Повідомимо про наявність";
      }


    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));

  }
}
