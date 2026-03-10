<?php

$dir = dirname(dirname(__FILE__));

// Include the necessary ocStore files
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

// Set language
$language = new Language($config->get('config_language'));
$language->load($config->get('config_language'));
$registry->set('language', $language);

// Load the necessary models
require_once(DIR_APPLICATION . 'model/localisation/city.php');
$model = new ModelLocalisationCity($registry);

$zone_ids = array();
if (isset($argv[1])) {
    $zone_ids = explode(',', $argv[1]);
}

echo $model->cronImportPosts($zone_ids) . PHP_EOL;
