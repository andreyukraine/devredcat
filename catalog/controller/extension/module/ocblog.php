<?php  
class ControllerExtensionModuleOcblog extends Controller
{
	public function index($setting) {

		$this->load->model('blog/article');
    $this->load->language('blog/blog');

		$data['text_headingtitle'] = $this->language->get('text_headingtitle');
		if (isset($setting['limit'])) {
			$limit = $setting['limit'];
		} else {
			$limit = 10;
		}

		if (isset($setting['rows'])) {
			$rows = $setting['rows'];
		} else {
			$rows = 1;
		}

		if (isset($setting['items'])) {
			$items = $setting['items'];
		} else {
			$items = 4;
		}

		if (isset($setting['speed'])) {
			$speed = $setting['speed'];
		} else {
			$speed = 3000;
		}

		if (isset($setting['auto']) && $setting['auto']) {
			$auto = true;
		} else {
			$auto = false;
		}

		if (isset($setting['navigation']) && $setting['navigation']) {
			$navigation = true;
		} else {
			$navigation = false;
		}

		if (isset($setting['pagination']) && $setting['pagination']) {
			$pagination = true;
		} else {
			$pagination = false;
		}

		$data['articles'] = array();

		$filter_data = array(
			'start'              => 0,
			'limit'              => $limit
		);

		$results = $this->model_blog_article->getArticlesByList($filter_data, $setting['list']);

		$this->load->model('tool/image');

    $width = 350;
    $height = 350;
    if ($this->mobile_detect->isMobile()) {
      $width = 150;
      $height = 150;
    }

		foreach ($results as $result) {

      if (isset($result['image'])) {
        $img_blog = !empty($result['image'] && file_exists(DIR_IMAGE . $result['image'])) ? $result['image'] : "no_image.png";
        $img = $this->model_tool_image->resize($img_blog, $width, $height);
      } else {
        $width = 200;
        $height = 200;
        $img_blog = "no_image.png";
        $img = $this->model_tool_image->resize($img_blog, $width, $height);
      }

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
      $date_added_m = $dateFormatter->format(new \DateTime($result['date_available']));

      $data['articles'][] = array(
				'article_id'    => $result['article_id'],
				'name'          => (utf8_strlen($result['name']) > 60 ? utf8_substr($result['name'], 0, 60) . '..' : $result['name']),
				'author'	      => $result['author'],
				'image'		      => $img,
        'width'         => $width,
        'height'        => $height,
				'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'date_added_m'  => ucfirst($date_added_m),
				'date_added_d'  => date("d", strtotime($result['date_available'])),
				'date_added_y'  => date("Y", strtotime($result['date_available'])),
				'intro_text'    => html_entity_decode($result['intro_text'], ENT_QUOTES, 'UTF-8'),
				'href'          => $this->url->link('blog/article', 'article_id=' . $result['article_id'])
			);
		}

		$data['slide'] = array(
			'auto' => $auto,
			'rows' => $rows,
			'navigation' => $navigation,
			'pagination' => $pagination,
			'speed' => $speed,
			'items' => $items
		);

		$data['button_read_more'] = $this->language->get('button_read_more');
		$data['text_empty'] = $this->language->get('text_blog_empty');

		return $this->load->view('blog/blog_home', $data);
    }
}
