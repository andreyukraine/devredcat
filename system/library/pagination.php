<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Pagination class
*/
class Pagination {
	public $total = 0;
	public $page = 1;
	public $limit = 20;
	public $num_links = 8;
	public $url = '';
	public $text_first = '|&lt;';
	public $text_last = '&gt;|';
	public $text_next = '&gt;';
	public $text_prev = '&lt;';

	/**
     * 
     *
     * @return	text
     */
	public function render() {
		$total = $this->total;

		if ($this->page < 1) {
			$page = 1;
		} else {
			$page = $this->page;
		}

		if (!(int)$this->limit) {
			$limit = 10;
		} else {
			$limit = $this->limit;
		}

		$num_links = $this->num_links;
		$num_pages = ceil((int)$total / (int)$limit);

		$this->url = str_replace('%7Bpage%7D', '{page}', $this->url);

    $output = '<div class="bl-pagination">';

    if ((int)$page - 1 <= 0) {
      $output .= '<div>&nbsp;</div>';
    } else {
      $output .= '<a href="' . str_replace('{page}', (int)$page - 1, $this->url) . '"><img class="pagination-prev-page" src="/image/prev.svg" alt="prev" height="15" width="35"></a>';
    }

		if ($page > 1) {
			//$output .= '<a href="' . str_replace(array('&amp;page={page}', '?page={page}', '&page={page}'), '', $this->url) . '">' . $this->text_first . '</a>';
		}

    $output .= '<ul class="pagination">';

		if ($num_pages > 1) {
			if ($num_pages <= $num_links) {
				$start = 1;
				$end = $num_pages;
			} else {
				$start = $page - floor($num_links / 2);
				$end = $page + floor($num_links / 2);

				if ($start < 1) {
					$end += abs($start) + 1;
					$start = 1;
				}

				if ($end > $num_pages) {
					$start -= ($end - $num_pages);
					$end = $num_pages;
				}
			}

			for ($i = $start; $i <= $end; $i++) {
				if ($page == $i) {
					$output .= '<li class="active"><span>' . $i . '</span></li>';
				} else {
					if ($i === 1) {
						$output .= '<li><a href="' . str_replace(array('&amp;page={page}', '/?page={page}', '&page={page}'), '', $this->url) . '">' . $i . '</a></li>';
					} else {
						$output .= '<li><a href="' . str_replace('{page}', $i, $this->url) . '">' . $i . '</a></li>';
					}
				}
			}
		}

		$output .= '</ul>';

    if ((int)$page < (int)$num_pages) {
      $output .= '<a href="' . str_replace('{page}', (int)$page + 1, $this->url) . '"><img class="pagination-next-page" src="/image/back.svg" alt="next" height="15" width="35"></a>';
    }else{
      $output .= '<div>&nbsp;</div>';
    }

    $output .= '</div>';

		if ($num_pages > 1) {
			return $output;
		} else {
			return '';
		}
	}
}
