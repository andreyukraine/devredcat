<?php

// Include the necessary ocStore files
$dir = dirname(dirname(__FILE__));

set_time_limit(0);
ini_set('memory_limit', '-1');

require_once($dir . '/admin/config.php');
require_once(DIR_SYSTEM . 'startup.php');

// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Config
$config = new Config();
$config->load('default');
$registry->set('config', $config);

// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'");
foreach ($query->rows as $setting) {
  if (!$setting['serialized']) {
    $config->set($setting['key'], $setting['value']);
  } else {
    $config->set($setting['key'], json_decode($setting['value'], true));
  }
}

// Set language based on config setting (if needed)
$language = new Language($config->get('config_language'));
$language->load($config->get('config_language'));
$registry->set('language', $language);

// Load the necessary models
require_once(DIR_APPLICATION . 'model/extension/module/ocimport.php'); // Replace with the actual path to the model file
$myModel = new ModelExtensionModuleOcimport($registry);
$myModel->cron_cards();
