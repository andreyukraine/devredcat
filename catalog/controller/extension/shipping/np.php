<?php

class ControllerExtensionShippingNp extends Controller
{

  public function index()
  {
    $this->load->language('checkout/np');

    if (isset($this->request->post['event'])) {
      switch ($this->request->post['event']) {
        case 'city':
          return $this->sendCity($this->request->post['q']);
        case 'post':
          return $this->sendPost($this->request->post['ref'], $this->request->post['q']);
        case 'address':
          return $this->sendAddress($this->request->post['ref'], $this->request->post['q']);
        case 'poshtomat':
          return $this->sendPoshtomat($this->request->post['ref'], $this->request->post['q']);
      }
    }
  }

  public function sendPoshtomat($ref, $query = '')
  {
    $this->load->model('setting/setting');
    $api_key = $this->model_setting_setting->getSettingValue('shipping_np_key');
    if (empty($api_key)) {
      $api_key = $this->config->get('shipping_np_key');
    }

    $request_data = [
      'apiKey' => $api_key,
      'modelName' => 'AddressGeneral',
      'calledMethod' => 'getWarehouses',
      'methodProperties' => [
        'SettlementRef' => $ref,
        'TypeOfWarehouseRef' => 'f9316480-5f2d-425d-bc2c-ac7cd29decf0', // Поштомати
        'FindByString' => $query,
        'Limit' => 250
      ]
    ];

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.novaposhta.ua/v2.0/json/',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_POSTFIELDS => json_encode($request_data),
      CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
    ));

    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    $json = [];

    if ($error) {
      $json[] = [
        "desc" => "Помилка з'єднання з Новою Поштою: " . $error,
        "ref" => '-'
      ];
    } else {
      $data = json_decode($response, true);
      
      if (isset($data['errors']) && !empty($data['errors'])) {
          $json[] = [
            "desc" => "Помилка Нової Пошти: " . implode(', ', $data['errors']),
            "ref" => '-'
          ];
      } else {
          if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $value) {
              $desc = $value['Description'];
              
              // На скріншоті видно, що адреса вже може бути в Description.
              // Додаємо ShortAddress тільки якщо його там немає.
              if (!empty($value['ShortAddress']) && mb_stripos($desc, $value['ShortAddress'], 0, 'UTF-8') === false) {
                $desc .= ': ' . $value['ShortAddress'];
              }
              
              // Фільтруємо результати, щоб вони дійсно містили пошуковий запит
              if (!empty($query)) {
                $q = mb_strtolower(trim($query), 'UTF-8');
                $d = mb_strtolower($desc, 'UTF-8');
                $number = isset($value['Number']) ? mb_strtolower(trim($value['Number']), 'UTF-8') : '';
                
                // Якщо це пошук за числом, перевіряємо точний збіг номера або входження в текст
                if (mb_strpos($d, $q) === false && mb_strpos($number, $q) === false) {
                  continue;
                }
              }
              
              $json[] = [
                "desc" => $desc,
                "ref" => $value['Ref'],
                "lat" => $value['Latitude'],
                "lon" => $value['Longitude']
              ];
            }
          }
      }

      // Якщо нічого не знайдено
      if (empty($json)) {
        $json[] = [
          "desc" => "Не знайдено поштоматів",
          "ref" => '-'
        ];
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function sendAddress($ref, $query)
  {
    if (!empty($query)) {

      $this->load->model('setting/setting');
      $api_key = $this->model_setting_setting->getSettingValue('shipping_np_key');
      if (empty($api_key)) {
        $api_key = $this->config->get('shipping_np_key');
      }

      $request_data = [
        'apiKey' => $api_key,
        'modelName' => 'Address',
        'calledMethod' => 'searchSettlementStreets',
        'methodProperties' => [
          'StreetName' => $query,
          'SettlementRef' => $ref,
          'Limit' => 100
        ]
      ];

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.novaposhta.ua/v2.0/json/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_data),
        CURLOPT_HTTPHEADER => array(
          'Content-Type: application/json'
        ),
      ));

      $response = curl_exec($curl);
      $error = curl_error($curl);
      curl_close($curl);

      $json = array();

      if ($error) {
        $json[] = array(
          "desc" => "Помилка з'єднання: " . $error,
          "ref" => '-',
        );
      } else {
        $response_data = json_decode($response, true);
        
        if (isset($response_data['errors']) && !empty($response_data['errors'])) {
            $json[] = array(
              "desc" => "Помилка API: " . implode(', ', $response_data['errors']),
              "ref" => '-',
            );
        } elseif (isset($response_data['data'][0]['Addresses']) && is_array($response_data['data'][0]['Addresses'])) {
          foreach ($response_data['data'][0]['Addresses'] as $value) {
            $json[] = array(
              "desc" => $value["Present"],
              "ref" => $value["SettlementStreetRef"],
            );
          }
        }
      }

      if (empty($json)) {
        $json[] = array(
          "desc" => "Не знайдено вулиць",
          "ref" => '-',
        );
      }

      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }

  public function sendPost($ref, $query = '')
  {
      $this->load->model('setting/setting');
      $api_key = $this->model_setting_setting->getSettingValue('shipping_np_key');
      if (empty($api_key)) {
        $api_key = $this->config->get('shipping_np_key');
      }

      $request_data = [
        'apiKey' => $api_key,
        'modelName' => 'AddressGeneral',
        'calledMethod' => 'getWarehouses',
        'methodProperties' => [
        'SettlementRef' => $ref,
        'TypeOfWarehouseRef' => '841339c7-591a-42e2-8233-7a0a00f0ed6f',
        'FindByString' => $query,
        'Limit' => 500
      ]
      ];

      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.novaposhta.ua/v2.0/json/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_data),
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
      ));

      $response = curl_exec($curl);
      $error = curl_error($curl);
      curl_close($curl);

      $json = array();
      
      if ($error) {
          $json[] = array(
            "desc" => "Помилка з'єднання: " . $error,
            "ref" => '-',
          );
      } else {
          $data = json_decode($response, true);

          if (isset($data['errors']) && !empty($data['errors'])) {
              $json[] = array(
                "desc" => "Помилка API: " . implode(', ', $data['errors']),
                "ref" => '-',
              );
          } elseif (isset($data['data'])) {
            foreach ($data['data'] as $value) {
              $desc = $value['Description'];
              
              if (!empty($value['ShortAddress']) && mb_stripos($desc, $value['ShortAddress'], 0, 'UTF-8') === false) {
                $desc .= ': ' . $value['ShortAddress'];
              }

              // Фільтруємо результати, щоб вони дійсно містили пошуковий запит
              if (!empty($query)) {
                $q = mb_strtolower(trim($query), 'UTF-8');
                $d = mb_strtolower($desc, 'UTF-8');
                $number = isset($value['Number']) ? mb_strtolower(trim($value['Number']), 'UTF-8') : '';
                
                if (mb_strpos($d, $q) === false && mb_strpos($number, $q) === false) {
                  continue;
                }
              }

              $json[] = array(
                "desc" => $desc,
                "ref" => $value["Ref"],
                "lat" => $value["Latitude"],
                "lon" => $value["Longitude"]
              );
            }
          }
      }

      // Якщо нічого не знайдено
      if (empty($json)) {
        $json[] = array(
          "desc" => "Не знайдено відділень",
          "ref" => '-',
        );
      }

      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
  }

  public function sendCity($query)
  {
    if (!empty($query)) {
      $this->load->model('setting/setting');
      $api_key = $this->model_setting_setting->getSettingValue('shipping_np_key');
      if (empty($api_key)) {
        $api_key = $this->config->get('shipping_np_key');
      }

      $request_data = [
        'apiKey' => $api_key,
        'modelName' => 'Address',
        'calledMethod' => 'searchSettlements',
        'methodProperties' => [
          'CityName' => $query,
          'Limit' => 50
        ]
      ];

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.novaposhta.ua/v2.0/json/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_data),
        CURLOPT_HTTPHEADER => array(
          'Content-Type: application/json'
        ),
      ));

      $response = curl_exec($curl);
      $error = curl_error($curl);
      curl_close($curl);

      $json = array();

      if ($error) {
        $json[] = array(
          "desc" => "Помилка з'єднання: " . $error,
          "ref" => '-',
        );
      } else {
        $response_data = json_decode($response, true);
        
        if (isset($response_data['errors']) && !empty($response_data['errors'])) {
            $json[] = array(
              "desc" => "Помилка API: " . implode(', ', $response_data['errors']),
              "ref" => '-',
            );
        } elseif (isset($response_data['data'][0]['TotalCount']) && $response_data['data'][0]['TotalCount'] > 0) {
          foreach ($response_data['data'][0]['Addresses'] as $value) {
            $json[] = array(
              "desc" => $value["Present"],
              "city" => $value["MainDescription"],
              "ref" => $value["Ref"],
              "delivery_ref" => $value["DeliveryCity"],
              "area" => $value["Area"]
            );
          }
        }
      }

      if (empty($json)) {
        $json[] = array(
          "desc" => 'Населений пункт не знайдений',
          "ref" => '-',
          "delivery_ref" => '-',
          "area" => '-'
        );
      }

      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }


}
