<?php

include_once(DIR_SYSTEM . 'library/ocsociallogin/login.php');

class ControllerocsocialloginRedirect extends Controller {

    public function index() {
        $this->language->load('ocsociallogin/redirect');
        $this->language->load('ocsociallogin/ocsociallogin');
        $this->load->model('setting/setting');
        $this->load->model('account/customer');

        $snsLogin = new Logins($this->registry);
        $result = $this->model_setting_setting->getSetting('ocsociallogin_setting', $this->config->get('config_store_id'));

        $data['snslogin_redirect_heading'] = $this->language->get('snslogin_redirect_heading');
        $data['snslogin_entry_email'] = $this->language->get('snslogin_entry_email');
        $data['snslogin_required_details_message'] = $this->language->get('snslogin_required_details_message');
        $data['snslogin_entry_phone'] = $this->language->get('snslogin_entry_phone');
        $data['snslogin_btn_submit'] = $this->language->get('snslogin_btn_submit');
        $data['error_facebook_login'] = $this->language->get('error_facebook_login');
        $data['error_google_login'] = $this->language->get('error_google_login');
        $data['error_linkedin_login'] = $this->language->get('error_linkedin_login');
        $data['error_live_login'] = $this->language->get('error_live_login');
        $data['error_twitter_login'] = $this->language->get('error_twitter_login');
        $data['error_yahoo_login'] = $this->language->get('error_yahoo_login');
        $data['error_amazon_login'] = $this->language->get('error_amazon_login');
        $data['error_instagram_login'] = $this->language->get('error_instagram_login');
        $data['error_paypal_login'] = $this->language->get('error_paypal_login');
        $data['error_paypal_authentication'] = $this->language->get('error_paypal_authentication');
        $data['error_login'] = $this->language->get('error_login');
        $data['email_already_exists'] = $this->language->get('email_already_exists');

        $this->session->data['social_user_details'] = array();

        if ($this->session->data['snslogin_provider'] == 'Google') {
            $logindata = $snsLogin->DoLogin('Google', $result['ocsociallogin_setting_google_client_id'], $result['ocsociallogin_setting_google_client_secret'], $this->url->link('ocsociallogin/redirect', '', 'SSL'), $data['error_google_login']);
            if ($logindata) {
                $this->addUser('Google', $logindata);
            } else {
                $this->session->data['error'] = $data['error_login'];
            }
        }

        if (isset($this->session->data['error']) && $this->session->data['error'] != "") {
            $this->response->redirect($this->url->link('extension/module/ocsociallogin/error', '', 'SSL'));
        }

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/kbsocilalogin/redirect.tpl')) {
            $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/ocsociallogin/redirect.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view('default/template/ocsociallogin/redirect.tpl', $data));
        }
    }

    public function addUser($source, $data) {
        $redirect_url = $this->url->link('account/account', '', 'SSL');
        $this->load->model('account/customer');
        $this->load->model('account/address');
        $this->load->model('ocsociallogin/ocsociallogin');

        if ($source == 'Google') {
            $this->session->data['google_login'] = true;
            $firstname = $data->given_name;
            $lastname = $data->family_name;
            $email = $data->email;
            $social_user_id = $data->id;
        }
        $this->session->data['social_user_details'] = array("id" => $social_user_id, "firstname" => $firstname, "lastname" => $lastname, "email" => $email, "type" => $source);

        $customer_data = $this->model_ocsociallogin_ocsociallogin->checkUser($social_user_id, $source);

        //If user already login with this social side id.
        if (!empty($customer_data)) {
            $this->model_ocsociallogin_ocsociallogin->addLoginHistory($customer_data['user_id'], $source, $firstname);
            $users_pass = $this->customer->login($email, '', true);
            $this->session->data['customer_id'] = $customer_data['oc_customer_id'];
            $this->response->redirect($redirect_url);
        } else {
            if (!empty($email)) {

                //If social side returns email ID. 
                $users_check = $this->model_account_customer->getCustomerByEmail($email);
                if (empty($users_check)) {
                    //If this email is not exist in the customer table
                    $user_info = array(
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'email' => $email,
                        'telephone' => '',
                        'fax' => '',
                        'password' => substr(md5(uniqid(rand(), true)), 0, 9),
                        'company' => '',
                        'company_id' => '',
                        'tax_id' => '',
                        'address_1' => '',
                        'address_2' => '',
                        'city' => '',
                        'postcode' => '',
                        'country_id' => '',
                        'zone_id' => '',
                        'customer_group_id' => 1,
                        'status' => 1,
                        'approved' => 1
                    );
                    $customer_id = $this->model_account_customer->addCustomer($user_info);
                    $this->model_account_address->addAddress($customer_id, $user_info);
                } else {
                    $customer_id = $users_check['customer_id'];
                }
                $this->model_ocsociallogin_ocsociallogin->addLoginUser($customer_id, $social_user_id, $source);

                $loginuser_detail = $this->model_ocsociallogin_ocsociallogin->checkUser($social_user_id, $source);
                $this->model_ocsociallogin_ocsociallogin->addLoginHistory($loginuser_detail['user_id'], $source, $firstname);
                $users_pass = $this->customer->login($email, '', true);
                //start by dharmanshu for the redirection
                if (isset($customer_id['customer_id']) && $customer_id['customer_id'] != '') {
                    $customer_id = $customer_id['customer_id'];
                } else {
                    $customer_id = $customer_id;
                }
                $this->session->data['customer_id'] = $customer_id;
                //end by dharmanshu for the redirection



                $this->response->redirect($redirect_url);
            } else {
                $this->response->redirect($this->url->link('ocsociallogin/capture_email', '', 'SSL'));
            }
        }
    }

}
