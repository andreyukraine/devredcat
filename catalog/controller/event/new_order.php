<?php

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Exception\TelegramException;

class ControllerEventNewOrder extends Controller {
    private static $processed_orders = [];

    /**
     * Event handler for checkout/order/addOrder after
     * 
     * @param string $route
     * @param array $data
     * @param int $output order_id
     */
    public function index(&$route, &$data, &$output) {
        $telegram_log = new Log('telegram.log');
        
        // В деяких версіях OpenCart route може бути 'checkout/order/addOrder' або 'catalog/model/checkout/order/addOrder/after'
        if ((strpos($route, 'checkout/order/addOrder') !== false) && $output) {
            $order_id = (int)$output;

            // Запобігання дублюванню повідомлень для одного і того ж замовлення в межах одного запиту
            if (isset(self::$processed_orders[$order_id])) {
                $telegram_log->write("Event Triggered (Duplicate Ignored): " . $route . " for Order #" . $order_id);
                return;
            }
            
            self::$processed_orders[$order_id] = true;

            $telegram_log->write("Event Triggered: " . $route);

            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);

            if ($order_info) {
                $this->sendPushToZoneUsers($order_info);
                $this->sendTelegram($order_info);
            }
        }
    }

    /**
     * Send telegram notification to main and manager bots
     * 
     * @param array $order_info
     */
    protected function sendTelegram($order_info) {
        $telegram_log = new Log('telegram.log');
        $telegram_log->write("Telegram: Preparing notification for Order #" . $order_info['order_id']);

        $point_name = "";
        if (!empty($order_info['client_address_id'])) {
          $this->load->model('account/address');
          $address = $this->model_account_address->getAddressForOrder($order_info['client_address_id'], $order_info['customer_id']);
          if ($address != null){
            $point_name = $address['firstname'];
          }
        }

        $text_telegram = "Нове замовлення №" . $order_info['order_id'] . "\n";
        $text_telegram .= $this->config->get('config_name') . "\n";
        $text_telegram .= "Клієнт: " . $order_info['firstname'] . " " . $order_info['lastname'] . "\n";
        if (!empty($point_name)){
          $text_telegram .= "Торгова точка: " . $point_name . "\n";
        }
        $text_telegram .= "Телефон: " . $order_info['telephone'] . "\n";
        $text_telegram .= "Сума: " . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']) . "\n";
        $text_telegram .= "Спосіб доставки: " . $order_info['shipping_method'] . "\n";
        $text_telegram .= "Спосіб оплати: " . $order_info['payment_method'] . "\n";

        //отримати назву регіона
        if (!empty($order_info['shipping_zone'])) {
            $text_telegram .= "Регіон: " . $order_info['shipping_zone'] . "\n";
        } elseif (!empty($order_info['customer_id'])) {
            $this->load->model('account/customer');
            $customer_info = $this->model_account_customer->getCustomer($order_info['customer_id']);
            if (!empty($customer_info['zone_id'])) {
                $this->load->model('localisation/zone');
                $zone_info = $this->model_localisation_zone->getZone($customer_info['zone_id']);
                if ($zone_info) {
                    $text_telegram .= "Регіон: " . $zone_info['name'] . "\n";
                }
            }
        }

        $text_telegram .= " " . "\n";
        $text_telegram .= "-------------------" . "\n";
        $text_telegram .= " " . "\n";

        $bot_username = $this->config->get('config_name');

        //загалтний чат
        if (!empty($this->config->get('occallback_main_bot'))) {
            try {
                $telegram_log->write("Telegram: Sending to main bot...");
                $telegram = new Telegram($this->config->get('occallback_main_bot'), $bot_username);
                $users = preg_split('/\r\n|\r|\n/', $this->config->get('occallback_main_bot_users'));
                
                if (!empty($users)) {
                    foreach ($users as $chat_id) {
                        $chat_id = trim($chat_id);
                        if (!empty($chat_id)) {
                            $result = Request::sendMessage([
                                'chat_id' => $chat_id,
                                'text'    => $text_telegram,
                                'parse_mode' => 'HTML'
                            ]);
                            $telegram_log->write("Telegram: Result for main bot chat " . $chat_id . ": " . ($result->isOk() ? "OK" : "Error: " . $result->getDescription()));
                        }
                    }
                }
            } catch (\Throwable $e) {
                $telegram_log->write("Telegram error (main bot): " . $e->getMessage());
            }
        }
    }

    /**
     * Find users whose group has the same zone as the customer and send them push notifications
     * 
     * @param array $order_info
     */
    protected function sendPushToZoneUsers($order_info) {
        $customer_id = (int)$order_info['customer_id'];
        
        // 1. Get zone_id from customer table
        $customer_query = $this->db->query("SELECT zone_id FROM `" . DB_PREFIX . "customer` WHERE customer_id = '" . $customer_id . "'");
        
        if ($customer_query->num_rows && $customer_query->row['zone_id']) {
            $zone_id = (int)$customer_query->row['zone_id'];
            
            // 2. Find all user groups that have this zone_id in their permissions
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

            if ($target_group_ids) {
                // 3. Find all admin users in these groups with an FCM token
                $user_query = $this->db->query("SELECT user_id, username, fcm_token FROM `" . DB_PREFIX . "user` WHERE user_group_id IN (" . implode(',', $target_group_ids) . ") AND fcm_token != '' AND status = '1'");
                
                if ($user_query->num_rows) {
                $push_data = [
                    'title'       => 'Замовлення №' . $order_info['order_id'] .'!',
                    'message'     => $order_info['firstname'] . ' ' . $order_info['lastname'],
                    'order_id'    => (string)$order_info['order_id'],
                    'OPEN_ORDERS' => 'true'
                ];

                $this->log->write("FCM: Starting push broadcast for Order #" . $order_info['order_id'] . " (Zone ID: " . $zone_id . ")");

                require_once(DIR_SYSTEM . 'library/fcm.php');
                $fcm = new Fcm($this->registry);

                foreach ($user_query->rows as $user) {
                    if (!empty($user['fcm_token'])) {
                        $result = $fcm->send($user['fcm_token'], $push_data);

                        if (isset($result['error'])) {
                            $this->log->write("FCM ERROR: Failed to send to user " . $user['username'] . " (ID: " . $user['user_id'] . "). Error: " . $result['error'] . " Response: " . $result['response']);
                        } else {
                            $this->log->write("FCM SUCCESS: Sent to user " . $user['username'] . " (ID: " . $user['user_id'] . "). Response: " . $result['response']);
                        }
                    }
                }
                }
            }
        }
    }
}
