<?php
class ControllerExtensionCaptchaBasic extends Controller {
	public function index($error = array()) {
		$this->load->language('extension/captcha/basic');

		if (isset($error['captcha'])) {
			$data['error_captcha'] = $error['captcha'];
		} else {
			$data['error_captcha'] = '';
		}

    $data['heading_title'] = $this->language->get('heading_title');
    $data['text_captcha'] = $this->language->get('text_captcha');

		$data['route'] = $this->request->get['route'];

    $data['user_token'] = '';
    if (isset($this->session->data['user_token'])) {
      $data['user_token'] = $this->session->data['user_token'];
    }

    $data['update'] = $this->url->link('extension/captcha/basic/update', 'user_token=' . $data['user_token'], true);

		return $this->load->view('extension/captcha/basic', $data);
	}

  public function update(){
    // Generate a new captcha
    $this->session->data['captcha'] = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 6);

    // Return JSON response
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode(['success' => true]));
  }

	public function validate() {
		$this->load->language('extension/captcha/basic');

		if (empty($this->session->data['captcha']) || ($this->session->data['captcha'] != $this->request->post['captcha'])) {
			return $this->language->get('error_captcha');
		}
	}

	public function captcha() {
		//$this->session->data['captcha'] = substr(sha1(mt_rand()), 15, 6);

//    $this->session->data['captcha'] = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'), 0, 6);
//
//		$image = imagecreatetruecolor(150, 35);
//
//		$width = imagesx($image);
//		$height = imagesy($image);
//
//		$black = imagecolorallocate($image, 0, 0, 0);
//		$white = imagecolorallocate($image, 255, 255, 255);
//		//$red = imagecolorallocatealpha($image, 255, 0, 0, 75);
//		//$green = imagecolorallocatealpha($image, 0, 255, 0, 75);
//		//$blue = imagecolorallocatealpha($image, 0, 0, 255, 75);
//
//		imagefilledrectangle($image, 0, 0, $width, $height, $white);
////		imagefilledellipse($image, ceil(rand(5, 145)), ceil(rand(0, 35)), 30, 30, $red);
////		imagefilledellipse($image, ceil(rand(5, 145)), ceil(rand(0, 35)), 30, 30, $green);
////		imagefilledellipse($image, ceil(rand(5, 145)), ceil(rand(0, 35)), 30, 30, $blue);
////		imagefilledrectangle($image, 0, 0, $width, 0, $black);
////		imagefilledrectangle($image, $width - 1, 0, $width - 1, $height - 1, $black);
////		imagefilledrectangle($image, 0, 0, 0, $height - 1, $black);
//		//imagefilledrectangle($image, 0, $height - 1, $width, $height - 1, $black);
//
//		imagestring($image, 40, intval(($width - (strlen($this->session->data['captcha']) * 9)) / 2), intval(($height - 15) / 2), $this->session->data['captcha'], $black);
//
//		header('Content-type: image/jpeg');
//
//		imagejpeg($image);
//
//		imagedestroy($image);

    $this->session->data['captcha'] = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 6);

// Увеличиваем размер изображения (например, 200x60 вместо 150x35)
    $image = imagecreatetruecolor(200, 50);

    $width = imagesx($image);
    $height = imagesy($image);

    $black = imagecolorallocate($image, 0, 0, 0);
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, 0, 0, $width, $height, $white);

// Используем imagettftext вместо imagestring для большего контроля
    $font_size = 24; // Размер шрифта
    $font = DIR_TEMPLATE . 'ukrwebua_theme/fonts/Raleway-Light.ttf'; // Укажите путь к файлу шрифта (например, arial.ttf)

// Рассчитываем позиции для каждой буквы отдельно
    $x = 20; // Начальная позиция по X
    $letter_spacing = 25; // Расстояние между буквами

    for ($i = 0; $i < strlen($this->session->data['captcha']); $i++) {
      $letter = $this->session->data['captcha'][$i];

      // Добавляем небольшую случайность к позиции Y для защиты от ботов
      $y = 30 + rand(-5, 5);

      // Рисуем каждую букву отдельно
      imagettftext($image, $font_size, rand(-10, 10), $x, $y, $black, $font, $letter);

      // Увеличиваем позицию X для следующей буквы
      $x += $letter_spacing;
    }

    header('Cache-Control: private, max-age=3600');
    header('Content-type: image/jpeg');
    imagejpeg($image);
    imagedestroy($image);

		exit();
	}
}
