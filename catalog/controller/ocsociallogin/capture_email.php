<?php

class ControllerOcsocialloginCaptureEmail extends Controller
{

    public function index()
    {
        $this->language->load('ocsociallogin/email');

        $this->load->model('setting/setting');
        $this->load->model('account/customer');

        if (!isset($this->session->data['social_user_details'])) {
            $this->response->redirect($this->url->link('common/home', '', 'SSL'));
        }
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_capture_email'),
            'href' => $this->url->link('ocsociallogin/capture_email', '', true)
        );
        $data['email_check_url'] = 'index.php?route=ocsociallogin/capture_email/check_email';

        $data['button_continue'] = $this->language->get('button_continue');
        $data['button_close'] = $this->language->get('button_close');
        $data['text_enter_email_id'] = $this->language->get('text_enter_email_id');
        $data['email_error'] = $this->language->get('email_error');
        $data['empty_field_error'] = $this->language->get('empty_field_error');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        if (VERSION < '2.2.0') {
            $this->response->setOutput($this->load->view('ocsociallogin/email.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view('ocsociallogin/email', $data));
        }
    }

    public function check_email()
    {
        $this->load->model('account/customer');
        $getemail = $this->request->post['email'];
        $users_check = $this->model_account_customer->getCustomerByEmail($getemail);
        if (empty($users_check)) {
            $success = 0;
        } else {
            $success = 1;
        }
        $json = $success;
        echo $json;
        die();
    }

    public function enteremail()
    {
        $this->load->model('account/customer');
        $this->load->model('account/address');
        $this->load->model('ocsociallogin/ocsociallogin');

        $firstname = $this->session->data['social_user_details']['firstname'];
        $lastname = $this->session->data['social_user_details']['lastname'];
        $email = $this->request->get['email'];
        $social_user_id = $this->session->data['social_user_details']['id'];
        $source = $this->session->data['social_user_details']['type'];
        unset($this->session->data['social_user_details']);
        
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
        if($customer_id) {
            $this->model_account_address->addAddress($customer_id, $user_info);

            $this->model_ocsociallogin_ocsociallogin->addLoginUser($customer_id, $social_user_id, $source);

            $loginuser_detail = $this->model_ocsociallogin_ocsociallogin->checkUser($social_user_id, $source);
            $this->model_ocsociallogin_ocsociallogin->addLoginHistory($loginuser_detail['user_id'], $source, $firstname);

            $this->customer->login($email, '', true);
            $this->session->data['customer_id'] = $customer_id;
        }
        $this->response->redirect($this->url->link('account/account', '', 'SSL'));
    }

}
