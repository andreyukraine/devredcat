<?php

class ModelOcsocialloginOcsociallogin extends Model {

    public function update_module_check($id, $data, $code) {
       $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "module WHERE name ='" . $data['name'] . "' AND module_id != '" . $id . "' AND code = '" . $code . "'");
       return($query);
    }

    public function add_module_check($data, $code) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "module WHERE name = '" . $data['name'] . "' AND code = '" . $code . " '");
        return($query);
    }
    
    public function addModule($code, $data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "module` SET `name` = '" . $this->db->escape($data['name']) . "', `code` = '" . $this->db->escape($code) . "', `setting` = '" . $this->db->escape(json_encode($data)) . "'");
    }
    
    public function get_id($data, $code) {
       $query = $this->db->query("SELECT module_id FROM " . DB_PREFIX . "module WHERE name ='" . $data['name'] . "' AND code = '" . $code . "'");
       return($query->rows['0']['module_id']);
    }

    public function insert_layout_module($data,$id,$code) {
        $code1 = $code.'.'.$id;
        $query = $this->db->query("INSERT INTO " . DB_PREFIX . "layout_module SET layout_id = '" . $data['layout_id'] . "', code = '".$code1."', position = '" . $data['position'] . "', sort_order = '" . (int) $data['sort_order'] . "'");
    }
    
    public function update_layout_module($data,$id,$code) {
        $code1 = $code.'.'.$id;
        $this->db->query("DELETE from " . DB_PREFIX . "layout_module  WHERE code = '".$code1."'");
        $query = $this->db->query("INSERT INTO " . DB_PREFIX . "layout_module SET layout_id = '" . $data['layout_id'] . "', code = '".$code1."', position = '" . $data['position'] . "', sort_order = '" . (int) $data['sort_order'] . "'");
    }
    
    public function layout_name($id){
        $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "layout WHERE layout_id ='" . $id ."'");
        return($query->rows['0']['name']);
    }
    
    public function add_icon_layout($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "ocsociallogin_layout` SET `name` = '".$data['layout_name']."', choose_login = '" . $this->db->escape(json_encode($data['choose_login'], true)) . "', icon_type = '" . $data['icon_type']  . "', hover_effect = '" . $data['hover_effect']  . "', button_color = '" . $data['button_color']  . "', color = '" . $this->db->escape(json_encode($data['color'],true)) . "' , alignment = '" . $data['alignment']  . "'");
    }
    
    public function select_all_icon_layout(){
        $query = $this->db->query("SELECT * FROM  `" . DB_PREFIX . "ocsociallogin_layout`");
        return $query->rows;  
    } 
    
    public function get_icon_layout($id){
        $query = $this->db->query("SELECT * FROM  `" . DB_PREFIX . "ocsociallogin_layout` where id ='" .$id."'");
        
        foreach ($query->rows as $result) {
            foreach ($result as $key => $value) {
                if ($key == 'color' || $key == 'choose_login') {
                    $setting_data[$key] = json_decode($value,true);
                } else {
                    $setting_data[$key] = $value;
                }
            }
        }

        return $setting_data;
    }
    
    public function update_icon_layout($data,$id){
        $this->db->query("UPDATE  `" . DB_PREFIX . "ocsociallogin_layout` SET `name` = '".$data['layout_name']."', choose_login = '" . $this->db->escape(json_encode($data['choose_login'],true)) . "', icon_type = '". $data['icon_type']."', hover_effect = '" . $data['hover_effect']  . "', button_color = '" . $data['button_color']  . "', color = '" . $this->db->escape(json_encode($data['color'],true)) . "' , alignment = '" . $data['alignment']  . "' WHERE id = '".$id."'");
    }
    
    public function delete_icon_layout($id){
        $this->db->query("DELETE FROM  `" . DB_PREFIX . "ocsociallogin_layout` WHERE id = '".$id."'");
    }
    
    public function update_icon_layout_check($data,$id) {
       
       $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ocsociallogin_layout WHERE name = '".$data['layout_name']."' AND id != '".$id."'");
     
       return($query);
    }

    public function add_icon_layout_check($data) {
         $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ocsociallogin_layout WHERE name= '".$data['layout_name']."'");
       
        return($query);
    }
    
    public function get_all_icon_layout_name(){
         $query = $this->db->query("SELECT name ,id FROM  `" . DB_PREFIX . "ocsociallogin_layout`");
        return $query->rows; 
    }
   
      public function  button_layout_name($id){
        $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "ocsociallogin_layout WHERE id ='" . $id ."'");
        return($query->rows['0']['name']);
    }
    
    public function  delete_icon_layout_check($id){
            $this->load->model('extension/module');
            $a= array();
          $results = $this->model_extension_module->getModulesByCode('ocsociallogin_module');
        foreach($results as $result){
        $module_info = $this->model_extension_module->getModule($result['module_id']);
        $a[]= $module_info['button_layout'];
        }
          //print_r($a); echo'<br/>'; echo $id;
          if(in_array($id, $a)) {
              return true;
          } else {
              return false;
          }
        
    }
    
     public function countregistercustomer(){
         $query = $this->db->query("SELECT  type , count(user_id) as count FROM " . DB_PREFIX . "ocsociallogin_users  GROUP BY type ");
          return $query->rows;
    }
     public function countlogincustomer(){
         $query = $this->db->query("SELECT  type , count(id) as count FROM " . DB_PREFIX . "ocsociallogin_login_history  GROUP BY type ");
          return $query->rows;
    }
    
    
    public function countlogincustomertype($type){
         $query = $this->db->query("SELECT  count(*) as count FROM " . DB_PREFIX . "ocsociallogin_login_history  WHERE type ='".$type."'");
          return $query->rows['0'];
    }
    
    public function checkaddmodule($code,$data){
        
        $query = $this->db->query("SELECT name , setting FROM " . DB_PREFIX . "module WHERE code ='".$code."'");
//        print_r($data); echo'<br/>';
//        print_r($query->rows);
        $var=0;
        foreach($query->rows as $subarray){
            $setting_array = json_decode($subarray['setting'],true);
            if($setting_array['layout_id'] == $data['layout_id'] && $setting_array['position'] == $data['position'] && $setting_array['sort_order'] == $data['sort_order'] ) {
            $var++;
           }
        }
        return $var;
      
       
        
    }
    public function checkupdatemodule($code,$data,$id){
        
        $query = $this->db->query("SELECT module_id ,name , setting FROM " . DB_PREFIX . "module WHERE code ='".$code."'");

        $var=0;
        foreach($query->rows as $subarray){
            $setting_array = json_decode($subarray['setting'],true);
            if($setting_array['layout_id'] == $data['layout_id'] && $setting_array['position'] == $data['position'] && $setting_array['sort_order'] == $data['sort_order'] && $subarray['module_id']!= $id ) {
            $var++;
           }
        }

        return $var;
      
       
        
    }
    
    public function customerlist($data = array()){
        $sql = "SELECT u.user_id,u.type,c.firstname,c.lastname,c.email FROM " . DB_PREFIX . "ocsociallogin_users as u inner join " . DB_PREFIX . "customer as c on u.oc_customer_id = c.customer_id ";
         if (!empty($data['email'])) {
            $sql .= " WHERE c.email = '" .  $data['email'] . "'";
        }
        
         if (isset($data['sort'])) {
             if($data['sort'] == 'name'){
               $sql .= " ORDER BY c.firstname";   
             }else if($data['sort'] == 'email'){
               $sql .= " ORDER BY c.email";   
             } else if($data['sort'] == 'type'){
               $sql .= " ORDER BY u.type";   
             } else {
              $sql .= " ORDER BY u.user_id";   
             }
        } else {
            $sql .= " ORDER BY u.user_id";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
        }
        $query = $this->db->query($sql);
        //print_r($query);  
       
        return $query->rows;
    }
   
    public function totalloginbyid($id){
          $query = $this->db->query("SELECT  count(*) as count FROM " . DB_PREFIX . "ocsociallogin_login_history WHERE user_id ='".$id."'");
            return $query->rows['0'];
    }
    public function counttotalregister($data = array()){
         if (isset($data['email']) && !empty($data['email'])) {
           
             $sql = "SELECT count(*) as total FROM " . DB_PREFIX . "ocsociallogin_users as u inner join " . DB_PREFIX . "customer as c on u.oc_customer_id = c.customer_id WHERE c.email = '" .  $data['email'] . "'";
        } else {
         $sql= "SELECT count(*) as total FROM `" . DB_PREFIX . "ocsociallogin_users`";
        }
         $query = $this->db->query($sql);
        return $query->row;
        
    }
    

    
}

?>
