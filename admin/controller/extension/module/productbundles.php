<?php

class ControllerExtensionModuleProductbundles extends Controller
{
  private $moduleName;
  private $modulePath;
  private $moduleModel;
  private $moduleVersion;
  private $extensionsLink;
  private $callModel;
  private $error = array();
  private $data = array();

  public function __construct($registry)
  {
    parent::__construct($registry);

    // Config Loader
    $this->config->load('isenselabs/productbundles');

    /* Fill Main Variables - Begin */
    $this->moduleName = $this->config->get('productbundles_name');
    $this->callModel = $this->config->get('productbundles_model');
    $this->modulePath = $this->config->get('productbundles_path');
    $this->moduleVersion = $this->config->get('productbundles_version');
    $this->extensionsLink = $this->url->link($this->config->get('productbundles_link'), 'user_token=' . $this->session->data['user_token'] . $this->config->get('productbundles_link_params'), 'SSL');
    /* Fill Main Variables - End */

    // Load Language
    $this->load->language($this->modulePath);

    // Load Model
    $this->load->model($this->modulePath);

    // Model Instance
    $this->moduleModel = $this->{$this->callModel};

    // Global Variables
    $this->data['moduleName'] = $this->moduleName;
    $this->data['modulePath'] = $this->modulePath;

    // Multi-Store
    $this->load->model('setting/store');
    // Settings
    $this->load->model('setting/setting');
    // Multi-Lingual
    $this->load->model('localisation/language');

    // Variables
    $this->data['moduleName'] = $this->moduleName;
    $this->data['modulePath'] = $this->modulePath;
  }

  public function index()
  {
    $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->moduleVersion);

    $this->load->model('catalog/product');
    $this->load->model('catalog/category');

    $this->document->addStyle('view/stylesheet/' . $this->moduleName . '/' . $this->moduleName . '.css');
    $this->document->addScript('view/javascript/' . $this->moduleName . '/' . $this->moduleName . '.js');

    if (!isset($this->request->get['store_id'])) {
      $this->request->get['store_id'] = 0;
    }

    $store = $this->getCurrentStore($this->request->get['store_id']);

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
//      if (!empty($_POST['OaXRyb1BhY2sgLSBDb21'])) {
//        $this->request->post[$this->moduleName]['LicensedOn'] = $_POST['OaXRyb1BhY2sgLSBDb21'];
//      }
//      if (!empty($_POST['cHRpbWl6YXRpb24ef4fe'])) {
//        $this->request->post[$this->moduleName]['License'] = json_decode(base64_decode($_POST['cHRpbWl6YXRpb24ef4fe']), true);
//      }
      if (isset($this->request->post[$this->moduleName . '_custom'])) {
        foreach ($this->request->post[$this->moduleName . '_custom'] as $bundle) {
          if (!isset($bundle['products'])) {
            unset($this->request->post[$this->moduleName . '_custom'][$bundle['id']]);
          }
        }
      }

      $this->model_setting_setting->editSetting($this->moduleName, $this->request->post, $this->request->post['store_id']);

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $store['store_id'], 'SSL'));
    }

    if (isset($this->session->data['success'])) {
      $this->data['success'] = $this->session->data['success'];
      unset($this->session->data['success']);
    } else {
      $this->data['success'] = '';
    }

    if (isset($this->error['warning'])) {
      $this->data['error_warning'] = $this->error['warning'];
    } else {
      $this->data['error_warning'] = '';
    }

    if ($this->config->get($this->moduleName . '_status')) {
      $this->data[$this->moduleName . '_status'] = $this->config->get($this->moduleName . '_status');
    } else {
      $this->data[$this->moduleName . '_status'] = '0';
    }

    $this->data['breadcrumbs'] = array();

    $this->data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
      'separator' => false
    );

    $this->data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_module'),
      'href' => $this->extensionsLink,
      'separator' => ' :: '
    );

    $this->data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title') . ' - ' . $this->moduleVersion,
      'href' => $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL'),
      'separator' => ' :: '
    );

    $languageVariables = array('heading_title', 'text_enabled', 'text_disabled', 'button_save', 'text_default', 'button_cancel', 'button_add_module', 'entry_yes', 'entry_no', 'button_remove');
    foreach ($languageVariables as $languageVariable) {
      $this->data[$languageVariable] = $this->language->get($languageVariable);
    }
    $this->data['store'] = $store;
    $languages = $this->model_localisation_language->getLanguages();
    $this->data['languages'] = $languages;
    $this->data['stores'] = array_merge(array(0 => array('store_id' => '0', 'name' => $this->config->get('config_name') . ' ' . $this->data['text_default'] . '', 'url' => HTTP_SERVER, 'ssl' => HTTPS_SERVER)), $this->model_setting_store->getStores());
    $this->data['heading_title'] = $this->language->get('heading_title') . ' - ' . $this->moduleVersion;

    foreach ($this->data['languages'] as $key => $value) {
      if (version_compare(VERSION, '2.2.0.0', "<")) {
        $this->data['languages'][$key]['flag_url'] = 'view/image/flags/' . $this->data['languages'][$key]['image'];
      } else {
        $this->data['languages'][$key]['flag_url'] = 'language/' . $this->data['languages'][$key]['code'] . '/' . $this->data['languages'][$key]['code'] . '.png"';
      }
    }
    $firstLanguage = array_shift($languages);
    $this->data['firstLanguageCode'] = $firstLanguage['code'];
    $this->data['firstLanguage'] = $firstLanguage;

    $price = $this->currency->getSymbolRight($this->config->get('config_currency'));
    if (!empty($price)) {
      $this->data['currencyAlignment'] = "R";
      $this->data['currency'] = $this->currency->getSymbolRight($this->config->get('config_currency'));
    } else {
      $this->data['currencyAlignment'] = "L";
      $this->data['currency'] = $this->currency->getSymbolLeft($this->config->get('config_currency'));
    }
    $this->data['config_currency'] = $this->config->get('config_currency');

    $this->data['user_token'] = $this->session->data['user_token'];
    $this->data['action'] = $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->request->get['store_id'], 'SSL');
    $this->data['cancel'] = $this->extensionsLink;

    $this->data['moduleSettings'] = $this->model_setting_setting->getSetting($this->moduleName, $store['store_id']);
    $this->data['moduleData'] = isset($this->data['moduleSettings'][$this->moduleName]) ? $this->data['moduleSettings'][$this->moduleName] : array();
    $this->data['CustomBundles'] = isset($this->data['moduleSettings'][$this->moduleName . '_custom']) ? $this->data['moduleSettings'][$this->moduleName . '_custom'] : array();

    $this->data['header'] = $this->load->controller('common/header');
    $this->data['column_left'] = $this->load->controller('common/column_left');
    $this->data['footer'] = $this->load->controller('common/footer');

    $this->data['model_catalog_product'] = $this->model_catalog_product;
    $this->data['model_catalog_category'] = $this->model_catalog_category;
    $this->data['currencyLibrary'] = $this->currency;

    $this->response->setOutput($this->load->view($this->modulePath . '/' . $this->moduleName . '.tpl', $this->data));
  }

  protected function validateForm()
  {
    if (!$this->user->hasPermission('modify', $this->modulePath)) {
      $this->error['warning'] = $this->language->get('error_permission');
    }
    return !$this->error;
  }

  public function install()
  {
    $this->moduleModel->install();
  }

  public function uninstall()
  {
    $this->load->model('setting/setting');
    $this->load->model('setting/store');

    $this->model_setting_setting->deleteSetting($this->moduleName, 0);
    $stores = $this->model_setting_store->getStores();

    foreach ($stores as $store) {
      $this->model_setting_setting->deleteSetting($this->moduleName, $store['store_id']);
    }

    $this->moduleModel->uninstall();
  }

  private function getCatalogURL()
  {
    if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
      $storeURL = HTTPS_CATALOG;
    } else {
      $storeURL = HTTP_CATALOG;
    }
    return $storeURL;
  }

  private function getCurrentStore($store_id)
  {
    if ($store_id && $store_id != 0) {
      $store = $this->model_setting_store->getStore($store_id);
    } else {
      $store['store_id'] = 0;
      $store['name'] = $this->config->get('config_name');
      $store['url'] = $this->getCatalogURL();
    }
    return $store;
  }
}

?>
