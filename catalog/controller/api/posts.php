<?php
class ControllerApiPosts extends Controller {
  public function index() {
    $secret = 'p9K7dZcLm92#S9@a';
    $expected_token = hash('sha256', $secret . date('Y-m-d'));

    $received = $this->request->get['token'] ?? '';

    if ($received !== $expected_token) {
      $this->response->addHeader('HTTP/1.0 403 Forbidden');
      $this->response->setOutput('Access denied');
      return;
    }

    // Мокаем список постов
    $posts = [
      ['title' => 'Пост 1', 'url' => '/blog/post1'],
      ['title' => 'Пост 2', 'url' => '/blog/post2'],
    ];

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($posts));
  }
}
