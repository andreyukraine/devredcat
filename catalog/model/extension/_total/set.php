<?php

class ModelExtensionTotalSet extends Model {

    public function getTotal($total) {

        if (isset($this->session->data['sets'])) {

            $this->load->language('extension/total/set');


            $discount = $this->calc();

            if (!$discount)
                return false;

            $total['totals'][] = array(
                'code' => 'set',
                'title' => sprintf($this->language->get('text_set')),
                'value' => -$discount,
                'sort_order' => $this->config->get('set_sort_order')
            );



            $total['total'] -= $discount;
        }
    }

    public function calc() {

        $cps = array();

        foreach ($this->cart->getProducts() as $key => $p) {
            $cps[$key]['q'] = (int) $p['quantity'];
            $cps[$key]['price'] = $p['price'];
            $cps[$key]['id'] = $p['product_id'];
            if ($p['option']) {
                foreach ($p['option'] as $opt)
                    $cps[$key]['option'][$opt['product_option_id']][] = (!empty($opt['product_option_value_id']) ? $opt['product_option_value_id'] : $opt['value']);
            }
        }

        $total_disc = 0;
        $cps = array_reverse($cps);
        if (!is_array($this->session->data['sets']))
            $this->session->data['sets'] = array();

        $sets = array_reverse($this->session->data['sets']);

        foreach ($sets as $set) {
            while (1) {
                $cs = array();

                $ccps = $cps;

                foreach ($set['products'] as $k => $p) {
                    $cs[$k] = array();

                    foreach ($ccps as $key => $cp) {

                        if ($p['product_id'] == $cp['id'] && $cp['q'] >= $p['quantity']) {
                            if (isset($p['use_option']) && $p['use_option'] == 'fixed') {
                                if (isset($cp['option']) && $this->check_opt($p['option'], $cp['option'])) {


                                    $n = 1;
                                    $cs[$k][$key] = $n;

                                    $ccps[$key]['q'] -= $n * $p['quantity'];
                                    continue 2;
                                } else {
                                    
                                }
                            } else {



                                $n = 1;
                                $cs[$k][$key] = $n;

                                $ccps[$key]['q'] -= $n * $p['quantity'];
                                continue 2;
                            }
                        }
                    }

                    if (count($cs[$k]) !== count($set['products'])) {
                        continue 3;
                    }
                }


                $total = 0;

                foreach ($cs as $c)
                    $arrmin[] = array_sum($c);

                $minc = min($arrmin);

                /* foreach ($cs as $key=>$counts)
                  {
                  $i=0;
                  $tc=0;
                  foreach($counts as $key2=>$c)
                  {
                  $tc+=$c;
                  $i++;
                  if($tc>=$minc)
                  {
                  if($tc>$minc)
                  $cs[$key][$key2]-=$tc-$minc;

                  $cs[$key]= array_slice ( $cs[$key], 0, $i,true);
                  break;
                  }
                  }
                  } */

                foreach ($cs as $key1 => $val1) {
                    foreach ($val1 as $key2 => $val2) {

                        $cps[$key2]['q'] -= $val2 * $set['products'][$key1]['quantity'];
                        $total += $cps[$key2]['price'] * ($val2 * $set['products'][$key1]['quantity']);
                    }
                }



					 $discount=0;
				if(isset($set['discount']))
                if (substr($set['discount'], -1) == '%') {
                    $prec = (int) rtrim($set['discount'], '%');
                    $discount = $total / 100 * $prec;
                } else
                    $discount = $set['discount'];


                $total_disc += $discount;
            }
        }

        return $total_disc;
    }

    public function debug($data) {
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
    }

    public function check_opt($need_opt, $c_opt) {


        foreach ($need_opt as $id => $val) {
            if (isset($c_opt[$id]) && (array_search($val, $c_opt[$id]) !== FALSE || (is_array($val) && $val == $c_opt[$id])))
                continue;
            // echo "<pre>";
            // var_dump($id);
            // var_dump($c_opt[$id]);exit();
            return false;
        }

        return true;
    }

}
