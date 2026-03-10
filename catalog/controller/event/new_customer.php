<?php

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;

class ControllerEventNewCustomer extends Controller {
    protected static $processed_customers = [];

    public function index(&$route, &$data, &$output) {
        if (strpos($route, 'account/customer/addCustomer') !== false) {
            $this->addCustomer($route, $data, $output);
        }
    }

    // model/account/customer/addCustomer/after
    public function addCustomer(&$route, &$args, &$output) {
        $customer_id = (int)$output;

        if (!$customer_id) {
            return;
        }

        if (isset(self::$processed_customers[$customer_id])) {
            return;
        }

        self::$processed_customers[$customer_id] = true;

        $customer_data = [];
        if (isset($args[0]) && is_array($args[0])) {
            $customer_data = $args[0];
        }

        $this->load->model('account/customer');
        $customer_info = $this->model_account_customer->getCustomer($customer_id);

        if ($customer_info) {
            $this->sendPushForNewCustomer($customer_info);
            $this->sendTelegramForNewCustomer($customer_info, $customer_data);
        }
    }

    protected function sendPushForNewCustomer($customer_info) {
        $customer_id = (int)$customer_info['customer_id'];
        $zone_id = !empty($customer_info['zone_id']) ? (int)$customer_info['zone_id'] : 0;

        if (!$customer_id || !$zone_id) {
            return;
        }

        $user_group_query = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group` WHERE permission LIKE '%\"zone\"%'");

        $target_group_ids = [];
        foreach ($user_group_query->rows as $group) {
            $permission = json_decode($group['permission'], true);

            if (isset($permission['zone']) && is_array($permission['zone'])) {
                $zones = array_map('intval', $permission['zone']);

                if (in_array($zone_id, $zones)) {
                    $target_group_ids[] = (int)$group['user_group_id'];
                }
            }
        }

        if (!$target_group_ids) {
            return;
        }

        $user_query = $this->db->query("SELECT user_id, username, fcm_token FROM `" . DB_PREFIX . "user` WHERE user_group_id IN (" . implode(',', $target_group_ids) . ") AND fcm_token != '' AND status = '1'");

        if (!$user_query->num_rows) {
            return;
        }

        $push_data = [
            'title'       => 'Новий клієнт #' . $customer_id . '!',
            'message'     => $customer_info['firstname'] . ' ' . $customer_info['lastname'],
            'customer_id' => (string)$customer_id,
            'OPEN_USERS'  => 'true'
        ];

        require_once(DIR_SYSTEM . 'library/fcm.php');
        $fcm = new Fcm($this->registry);

        foreach ($user_query->rows as $user) {
            if (!empty($user['fcm_token'])) {
                $fcm->send($user['fcm_token'], $push_data);
            }
        }
    }

    protected function sendTelegramForNewCustomer($customer_info, $customer_data = []) {
        $customer_group_name = '';
        if (!empty($customer_data['customer_group_id'])) {
            $this->load->model('account/customer_group');
            $customer_group = $this->model_account_customer_group->getCustomerGroup((int)$customer_data['customer_group_id']);
            if (!empty($customer_group)) {
                $customer_group_name = $customer_group['name'];
            }
        }

        $text_telegram = "Нова реєстрація на сайті" . "\n";
        $text_telegram .= $this->config->get('config_name') . "\n";
        $text_telegram .= "Код на сайті: " . $customer_info['customer_id'] . "\n";
        $text_telegram .= "Тип кліента: " . $customer_group_name . "\n";
        $text_telegram .= "Імя: " . $customer_info['firstname'] . "\n";
        $text_telegram .= "Призвище: " . $customer_info['lastname'] . "\n";
        $text_telegram .= "Пошта: " . $customer_info['email'] . "\n";
        $text_telegram .= "Телефон: " . $customer_info['telephone'] . "\n";

        if (!empty($customer_info['zone_id'])) {
            $this->load->model('localisation/zone');
            $zone_info = $this->model_localisation_zone->getZone((int)$customer_info['zone_id']);
            if ($zone_info) {
                $text_telegram .= "Регіон: " . $zone_info['name'] . "\n";
            }
        }

        $text_telegram .= " " . "\n";
        $text_telegram .= "-------------------" . "\n";
        $text_telegram .= " " . "\n";

        if (empty($this->config->get('occallback_main_bot'))) {
            return;
        }

        $bot_username = $this->config->get('config_name');

        try {
            $telegram = new Telegram($this->config->get('occallback_main_bot'), $bot_username);
            $users = preg_split('/\r\n|\r|\n/', $this->config->get('occallback_main_bot_users'));

            if (!empty($users)) {
                foreach ($users as $chat_id) {
                    $chat_id = trim($chat_id);
                    if (!empty($chat_id)) {
                        Request::sendMessage([
                            'chat_id' => $chat_id,
                            'text' => $text_telegram,
                            'parse_mode' => 'HTML'
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->log->write("Telegram error (new customer): " . $e->getMessage());
        }
    }
}
