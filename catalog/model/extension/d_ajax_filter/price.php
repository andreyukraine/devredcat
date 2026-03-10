<?php
class ModelExtensionDAjaxFilterPrice extends Model {

    private $codename="d_ajax_filter";

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('extension/module/'.$this->codename);
    }

    public function getPriceForCategory($data)
    {
        $data['params'] = $params = $this->{'model_extension_module_'.$this->codename}->getParamsToArray(true);

        $total_query = $this->{'model_extension_module_'.$this->codename}->getProductsQuery(true);

        $sql = " SELECT min(p.af_price) as min, max(p.af_price) as max 
        FROM (".$total_query.") p";

        if (!empty($params)) {
            unset($params['price']);
            $result = $this->{'model_extension_module_'.$this->codename}->getParamsQuery($params);
            if(!empty($result)){
                $sql.=" WHERE ".$result;
            }
        }

        //$hash = md5(json_encode($data));

//        $result = $this->cache->get('af-price.' . $hash);
//        if(!$result){
            $query = $this->db->query($sql);
            $result = $query->row;
            //$this->cache->set('af-price.' .$hash , $result);
//        }
        return $result;
    }

    public function getTotalQuery($price, $table_name){

      if ($table_name == "aft") {
        $price = $price[0];

        if (count($price) == 1) {
          $tmp = $price;
          $price[0] = $tmp;
          $price[1] = $tmp;
        }

        $min = $this->currency->convert($price[0], $this->session->data['currency'], $this->config->get('config_currency'));
        $max = $this->currency->convert($price[1], $this->session->data['currency'], $this->config->get('config_currency'));
        $sql = $table_name . ".af_price BETWEEN '" . round($min) . "' AND '" . round($max) . "'";
        return $sql;
      }
    }
}
