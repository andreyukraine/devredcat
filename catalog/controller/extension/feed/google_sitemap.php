<?php
class ControllerExtensionFeedGoogleSitemap extends Controller {
	public function index() {
		if ($this->config->get('feed_google_sitemap_status')) {
			$output  = '<?xml version="1.0" encoding="UTF-8"?>';
			$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

      //товари
			$this->load->model('catalog/product');
      $this->load->model('catalog/category');
      $this->load->model('catalog/manufacturer');
			$this->load->model('tool/image');
      $this->load->model('catalog/information');
      $this->load->model('blog/category');
      $this->load->model('blog/article');

      //товари
			$products = $this->model_catalog_product->getProducts();
      if (!empty($products['products'])) {
        foreach ($products['products'] as $product) {
          if ($product['image']) {
            $output .= '<url>';
            $output .= '  <loc>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</loc>';
            $output .= '  <changefreq>weekly</changefreq>';
            $output .= '  <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($product['date_modified'])) . '</lastmod>';
            $output .= '  <priority>1.0</priority>';
            $output .= '  <image:image>';
            $output .= '  <image:loc>' . $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')) . '</image:loc>';
            $output .= '  <image:caption>' . (!empty($product['name']) ? htmlspecialchars($product['name'], ENT_XML1 | ENT_QUOTES, 'UTF-8') : "name empty") . '</image:caption>';
            $output .= '  <image:title>' . (!empty($product['name']) ? htmlspecialchars($product['name'], ENT_XML1 | ENT_QUOTES, 'UTF-8') : "name empty") . '</image:title>';
            $output .= '  </image:image>';
            $output .= '</url>';
          }
        }
      }


      //категорії по брендам
			$output .= $this->getCategories(0, 0);

      //категорії по типам
      $output .= $this->getCategories(0, 1);

      //бренди
			$manufacturers = $this->model_catalog_manufacturer->getManufacturers();
			foreach ($manufacturers as $manufacturer) {
				$output .= '<url>';
				$output .= '  <loc>' . $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id']) . '</loc>';
				$output .= '  <changefreq>weekly</changefreq>';
				$output .= '  <priority>0.7</priority>';
				$output .= '</url>';

//				$products = $this->model_catalog_product->getProducts(array('filter_manufacturer_id' => $manufacturer['manufacturer_id']));
//        if (!empty($products['products'])) {
//          foreach ($products['products'] as $product) {
//            $output .= '<url>';
//            $output .= '  <loc>' . $this->url->link('product/product', 'manufacturer_id=' . $manufacturer['manufacturer_id'] . '&product_id=' . $product['product_id']) . '</loc>';
//            $output .= '  <changefreq>weekly</changefreq>';
//            $output .= '  <priority>1.0</priority>';
//            $output .= '</url>';
//          }
//        }
			}

      //статті
			$informations = $this->model_catalog_information->getInformations();
			foreach ($informations as $information) {
				$output .= '<url>';
				$output .= '  <loc>' . $this->url->link('information/information', 'information_id=' . $information['information_id']) . '</loc>';
				$output .= '  <changefreq>weekly</changefreq>';
				$output .= '  <priority>0.5</priority>';
				$output .= '</url>';
			}

      //статті блогу
      $output .= $this->getBlog();

      //інші сторінки
      $output .= '<url>';
      $output .= '  <loc>' . $this->url->link('information/contact') . '</loc>';
      $output .= '  <changefreq>weekly</changefreq>';
      $output .= '  <priority>1.0</priority>';
      $output .= '</url>';

			$output .= '</urlset>';

			$this->response->addHeader('Content-Type: application/xml');
			$this->response->setOutput($output);
		}
	}

	protected function getCategories($parent_id, $type, $current_path = '') {

		$output = '';

		$results = $this->model_catalog_category->getCategories($parent_id, $type);

		foreach ($results as $result) {
			if (!$current_path) {
				$new_path = $result['category_id'];
			} else {
				$new_path = $current_path . '_' . $result['category_id'];
			}

			$output .= '<url>';
			$output .= '  <loc>' . $this->url->link('product/category', 'path=' . $new_path) . '</loc>';
			$output .= '  <changefreq>weekly</changefreq>';
			$output .= '  <priority>0.7</priority>';
			$output .= '</url>';

//			$products = $this->model_catalog_product->getProducts(array('filter_category_id' => $result['category_id']));
//
//      if (!empty($products['products'])) {
//        foreach ($products['products'] as $product) {
//          $output .= '<url>';
//          $output .= '  <loc>' . $this->url->link('product/product', 'path=' . $new_path . '&product_id=' . $product['product_id']) . '</loc>';
//          $output .= '  <changefreq>weekly</changefreq>';
//          $output .= '  <priority>1.0</priority>';
//          $output .= '</url>';
//        }
//      }

			$output .= $this->getCategories($result['category_id'], $new_path);
		}

		return $output;
	}

  protected function getBlog(){

    $output = '';

    $categories = $this->model_blog_category->getCategories(0);

    foreach ($categories as $category) {

        // Level 2
        $children = $this->model_blog_category->getCategories($category['blog_category_id']);

        foreach ($children as $child) {

          $output .= '<url>';
          $output .= '  <loc>' . $this->url->link('blog/category', 'blog_category_id=' . $category['blog_category_id'] . '_' . $child['blog_category_id']) . '</loc>';
          $output .= '  <changefreq>weekly</changefreq>';
          $output .= '  <priority>0.7</priority>';
          $output .= '</url>';

          $articles_child = $this->model_blog_article->getArticles(array('filter_blog_category_id' => $child['blog_category_id']));

          foreach ($articles_child as $article_child) {
            $output .= '<url>';
            $output .= '  <loc>' . $this->url->link('blog/article', 'blog_category_id=' . $category['blog_category_id'] . '_' . $child['blog_category_id'] . '&article_id=' . $article_child['article_id']) . '</loc>';
            $output .= '  <changefreq>weekly</changefreq>';
            $output .= '  <priority>1.0</priority>';
            $output .= '</url>';
          }
        }

        $output .= '<url>';
        $output .= '  <loc>' . $this->url->link('blog/category', 'blog_category_id=' . $category['blog_category_id']) . '</loc>';
        $output .= '  <changefreq>weekly</changefreq>';
        $output .= '  <priority>0.7</priority>';
        $output .= '</url>';

        $articles = $this->model_blog_article->getArticles(array('filter_blog_category_id' => $category['blog_category_id']));

        foreach ($articles as $article) {
          $output .= '<url>';
          $output .= '  <loc>' . $this->url->link('blog/article', 'blog_category_id=' . $category['blog_category_id'] . '&article_id=' . $article['article_id']) . '</loc>';
          $output .= '  <changefreq>weekly</changefreq>';
          $output .= '  <priority>1.0</priority>';
          $output .= '</url>';
        }
      }

    return $output;
  }

}
