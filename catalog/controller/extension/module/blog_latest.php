<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerExtensionModuleBlogLatest extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/blog_latest');

		$this->load->model('blog/article');

		$this->load->model('tool/image');

		$data['articles'] = array();

		$filter_data = array(
			'sort'  => 'p.date_added',
			'order' => 'DESC',
			'start' => 0,
			'limit' => $setting['limit']
		);

		$results = $this->model_blog_article->getArticles($filter_data);

    $data['width'] = 500;
    $data['height'] = 300;
    if ($this->mobile_detect->isMobile()) {
      $data['width'] = 250;
      $data['height'] = 150;
    }

		if ($results) {
			foreach ($results as $result) {
				if ($result['image'] && file_exists(DIR_IMAGE . $result['image'])) {

          list($width_orig, $height_orig) = getimagesize(DIR_IMAGE . $result['image']);
          $height = ($data['width'] / $width_orig) * $height_orig;

					$image = $this->model_tool_image->resize($result['image'], $data['width'], $height);
				} else {

          $data['width'] = 200;
          $data['height'] = 200;

          list($width_orig, $height_orig) = getimagesize(DIR_IMAGE . 'no_image.png');

					$image = $this->model_tool_image->resize('no_image.png', $data['width'], $data['height']);
				}

				if ($this->config->get('configblog_review_status')) {
					$rating = $result['rating'];
				} else {
					$rating = false;
				}

				$data['articles'][] = array(
					'article_id'  => $result['article_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('configblog_article_description_length')) . '..',
					'rating'      => $rating,
					'date_added'  => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
					'href'        => $this->url->link('blog/article', 'article_id=' . $result['article_id'])
				);
			}

      $data['module_id'] = rand(1, 1000);

			return $this->load->view('extension/module/blog_latest', $data);
		}
	}
}
