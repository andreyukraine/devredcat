<?php
class ControllerCommonCountdown extends Controller {
  public function server_time() {
    // Встановлюємо заголовок JSON
    $this->response->addHeader('Content-Type: application/json');

    // Поточний час сервера у форматі ISO 8601 (UTC)
    $serverTime = new DateTime('now', new DateTimeZone('UTC'));
    $data = [
      'server_time' => $serverTime->format('Y-m-d H:i:s'),
      'timestamp'   => $serverTime->getTimestamp()
    ];

    // Відправляємо JSON-відповідь
    $this->response->setOutput(json_encode($data));
  }
}
