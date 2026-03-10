<?php

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Exception\TelegramException;

class ControllerExtensionModuleOccallback extends Controller
{
  private $error = [];

  public function index()
  {
    if ($this->config->get('occallback_status') && isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      $this->load->language('extension/module/occallback');

      $data['heading_title'] = $this->language->get('heading_title');
      $data['occallback_data'] = $occallback_data = $this->config->get('occallback_data');

      $data['name'] = $this->customer->isLogged() ? $this->customer->getFirstName() : '';
      $data['telephone'] = $this->customer->isLogged() ? $this->customer->getTelephone() : '';

      $data['comment'] = '';
      $data['mask'] = (isset($occallback_data['mask']) && !empty($occallback_data['mask'])) ? $occallback_data['mask'] : '';

      // Captcha
      if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status')) {
        $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
      } else {
        $data['captcha'] = '';
      }

      $this->response->setOutput($this->load->view('extension/module/occallback', $data));
    } else {
      $this->response->redirect($this->url->link('error/not_found', '', true));
    }
  }

  public function send()
  {
    if ($this->config->get('occallback_status') && isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      $json = [];

      $this->language->load('extension/module/occallback');

      if ($this->validate()) {
        $this->load->model('extension/module/occallback');

        $occallback_data = $this->config->get('occallback_data');

        $data = [];

        if (isset($this->request->post['name'])) {
          $data[] = [
            'name' => $this->language->get('enter_name'),
            'value' => $this->request->post['name']
          ];
        }

        if (isset($this->request->post['telephone'])) {
          $data[] = [
            'name' => $this->language->get('enter_telephone'),
            'value' => $this->request->post['telephone']
          ];
        }

        if (isset($this->request->post['comment'])) {
          $data[] = [
            'name' => $this->language->get('enter_comment'),
            'value' => $this->request->post['comment']
          ];
        }

        if (isset($this->request->post['url_page'])) {
          $data[] = [
            'name' => $this->language->get('enter_url_page'),
            'value' => $this->request->post['url_page']
          ];
        }

        $data_send = [
          'info' => serialize($data)
        ];

        $this->model_extension_module_occallback->addRequest($data_send);

        if (isset($occallback_data['notify_status']) && $occallback_data['notify_status']) {
          $html_data['date_added'] = date('d.m.Y H:i:s', time());
          //$html_data['logo'] = $this->config->get('config_url') . 'image/' . $this->config->get('config_logo');

          if ($this->request->server['HTTPS']) {
            $server = $this->config->get('config_ssl');
          } else {
            $server = $this->config->get('config_url');
          }

          if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
            $html_data['logo'] = $server . 'image/' . $this->config->get('config_logo');
          } else {
            $html_data['logo'] = '';
          }


          $html_data['store_name'] = $this->config->get('config_name');
          $html_data['store_url'] = $this->config->get('config_url');
          $html_data['text_info'] = $this->language->get('text_info');
          $html_data['text_date_added'] = $this->language->get('text_date_added');
          $html_data['data_info'] = $data;

          $html_data['t_data'] = $this->load->controller('mail/template_data');

          $html = $this->load->view('mail/occallback_mail', $html_data);

          $mail = new Mail();
          $mail->protocol = $this->config->get('config_mail_engine');
          $mail->parameter = $this->config->get('config_mail_parameter');
          $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
          $mail->smtp_username = $this->config->get('config_mail_smtp_username');
          $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
          $mail->smtp_port = $this->config->get('config_mail_smtp_port');
          $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

          //++Andrey
          //кому
          $mail->setTo($this->config->get('config_email'));
          //від кого
          $mail->setFrom($this->config->get('config_email'));
          $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
          $mail->setSubject(html_entity_decode($this->language->get('heading_title'), ENT_QUOTES, 'UTF-8') . " -- " . $html_data['date_added']);
          $mail->setHtml($html);

          $mail->send();

          $emails = explode(',', $this->config->get('config_mail_alert_email'));

          foreach ($emails as $email) {
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
              $mail->setTo($email);
              $mail->send();
            }
          }
        }


        //надсилаємо в телеграм повідомлення
        $text_telegram = " " . html_entity_decode($this->language->get('heading_title'), ENT_QUOTES, 'UTF-8') . " -- " . $html_data['date_added'] . "\n";
        $text_telegram .= $this->config->get('config_name') . "\n";
        $text_telegram .= "Імя: " . $this->request->post['name'] . "\n";
        $text_telegram .= "Телефон: " . $this->request->post['telephone'] . "\n";
        $text_telegram .= " " . "\n";
        $text_telegram .= "-------------------" . "\n";
        $text_telegram .= " " . "\n";

        //загалтний чат
        if (!empty($this->config->get('occallback_main_bot'))) {
          $bot_username = $this->config->get('config_name');

          try {
            // Ініціалізуйте об'єкт Telegram
            $telegram = new Telegram($this->config->get('occallback_main_bot'), $bot_username);

            // Розділяємо рядок на масив, використовуючи новий рядок як роздільник
            $array = explode("\r\n", $this->config->get('occallback_main_bot_users'));
            if (!empty($array)) {
              for ($i = 0; $i < count($array); $i++) {
                if (!empty($array[$i])) {
                  $chat_id = $array[$i];
                  // Відправка повідомлення
                  $result = Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => $text_telegram,
                    'parse_mode' => 'HTML'
                  ]);
                }
              }
            }
          } catch (TelegramException $e) {
            // Обробка помилок бібліотеки
            //echo 'Telegram API помилка: ' . $e->getMessage();
          } catch (Exception $e) {
            // Обробка інших помилок
            //echo 'Інша помилка: ' . $e->getMessage();
          }
        }

        $html_output = '';
        $html_output .= '<div class="p-50">';
        $html_output .= $this->language->get('text_success_send');
        $html_output .= '</div>';

        $json['output'] = $html_output;
      } else {
        $json['error'] = $this->error;
      }

      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->response->redirect($this->url->link('error/not_found', '', true));
    }
  }

  protected
  function validate()
  {
    $occallback_data = $this->config->get('occallback_data');

    if (isset($this->request->post['name']) && (utf8_strlen(trim($this->request->post['name'])) < 1 || utf8_strlen(trim($this->request->post['name'])) > 32)) {
      $this->error['name'] = $this->language->get('error_name');
    }

    if (isset($this->request->post['telephone'])) {
      $this->request->post['telephone'] = preg_replace("/[^0-9,\(\),\-,_,+]/", '', $this->request->post['telephone']);
    }

    if (isset($this->request->post['telephone']) && !empty($occallback_data['mask'])) {
      if (!empty($this->request->post['telephone'])) {
        $phone_count = utf8_strlen(str_replace(['_', '-', '(', ')', '+', ' '], "", $occallback_data['mask']));
        if ((isset($occallback_data['telephone']) && $occallback_data['telephone'] == 2) && (utf8_strlen(str_replace(['_', '-', '(', ')', '+', ' '], "", $this->request->post['telephone'])) < $phone_count || !preg_match('/[0-9,\-,+,\(,\),_]/', $this->request->post['telephone']))) {
          $this->error['telephone'] = $this->language->get('error_telephone_mask');
        }
      } else {
        $this->error['telephone'] = $this->language->get('error_telephone');
      }
    } elseif (isset($this->request->post['telephone']) && ((isset($occallback_data['telephone']) && $occallback_data['telephone'] == 2) && (utf8_strlen(str_replace(['_', '-', '(', ')', '+', ' '], "", $this->request->post['telephone'])) > 15 || utf8_strlen(str_replace(['_', '-', '(', ')', '+', ' '], "", $this->request->post['telephone'])) < 3) || !preg_match('/[0-9,\-,+,\(,\),_]/', $this->request->post['telephone']))) {
      $this->error['telephone'] = $this->language->get('error_telephone');
    } else {
      $this->error['telephone'] = $this->language->get('error_telephone');
    }

    // Captcha
    if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status')) {
      $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

      if ($captcha) {
        $this->error['captcha'] = $captcha;
      }
    }

    return !$this->error;
  }
}
