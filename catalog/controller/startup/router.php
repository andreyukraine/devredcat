<?php
class ControllerStartupRouter extends Controller {
  public function index() {

//    Додаємо обробку нашого нового маршруту
    if (isset($this->request->get['route']) && $this->request->get['route'] == 'common/countdown/server_time') {
      $route = $this->request->get['route'];
      $action = new Action($route);
      return $action->execute($this->registry);
    }

    // Стандартна логіка маршрутизації
    if (isset($this->request->get['route']) && $this->request->get['route'] != 'startup/router') {
      $route = $this->request->get['route'];
    } else {
      $route = $this->config->get('action_default');
    }

    // Sanitize the call
    $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);

    // Trigger the pre events
    $result = $this->event->trigger('controller/' . $route . '/before', array(&$route, &$data));

    if (!is_null($result)) {
      return $result;
    }

    $action = new Action($route);
    $output = $action->execute($this->registry);

    // Trigger the post events
    $result = $this->event->trigger('controller/' . $route . '/after', array(&$route, &$data, &$output));

    if (!is_null($result)) {
      return $result;
    }

    return $output;
  }
}
