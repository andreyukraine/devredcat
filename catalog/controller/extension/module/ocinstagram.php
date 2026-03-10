<?php

class ControllerExtensionModuleOcinstagram extends Controller
{
  public function index($setting)
  {
    $this->load->language('extension/module/ocinstagram');

    $this->load->model('extension/module/ocinstagram');

    $this->document->addScript('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.pack.js');
    $this->document->addStyle('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.css');

    // Определяем язык в зависимости от кода
    $locale = 'uk_UA'; // По умолчанию украинский
    switch ($this->language->get('code')) {
      case "en":
        $locale = 'en_US';
        break;
      case "ru":
        $locale = 'ru_RU';
        break;
      case "uk":
        $locale = 'uk_UA';
        break;
    }

    // Используем IntlDateFormatter для форматирования даты
    $dateFormatter = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'MMM');

    $data = array();
    $data['text_copyright'] = sprintf($this->language->get('text_copyright'), $this->config->get('config_name'));

    $total = $this->model_extension_module_ocinstagram->getTotalPosts();

    $width = 350;
    $height = 350;
    if ($this->mobile_detect->isMobile()) {
      $width = 150;
      $height = 150;
    }

    $data['instagrams'] = array();

    $posts = $this->model_extension_module_ocinstagram->getPostList(0, $total);
    if (!empty($posts)) {
      foreach ($posts as $key => $post) {

        if (isset($post['image'])) {
          $img = $this->model_tool_image->resize($post['image'], $width, $height);
        } else {
          $img = $this->model_tool_image->resize("no_image.png", $width, $height);
        }

        $date_added_m = $dateFormatter->format(new \DateTime(date('m/d/Y', strtotime($post['created_time']))));

        $data['instagrams'][] = array(
          'title' => $post['caption'] ? (utf8_strlen($post['caption']) > 120 ? utf8_substr($post['caption'], 0, 60) . '..' : $post['caption']) : '',
          'caption' => $post['caption'] ?: '',
          'image' => $img,
          'width' => $width,
          'height' => $height,
          'link' => $post['link'],
          'created_time' => date('m/d/Y', strtotime($post['created_time'])),
          'date_added' => date($this->language->get('date_format_short'), strtotime(strtotime($post['created_time']))),
          'date_added_m' => ucfirst($date_added_m),
          'date_added_d' => date("d", strtotime($post['created_time'])),
          'date_added_y' => date("Y", strtotime($post['created_time'])),
        );
      }
    }

    return $this->load->view('extension/module/ocinstagram', $data);
  }
}
