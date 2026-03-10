<?php
class Logins {

    public $registry;
    public $liveEnabled, $googleEnabled;

    public function __construct($registry) {
        $this->registry = $registry;
    }

    public function getButtons() {
        $str = '<form class="form" name="snslogin" id="form" method="post">'
                . '<p>Or Login Via</p>'
                . '<p>'
                . '<input type = "submit" value = "Google" class = "button" name="GoogleLogin"/>'
                . '</form>';
        return $str;

    }

    public function DoLogin($buttonValue, $clientID, $clientSecret, $redirectURL, $errorMessage) {
        if ($buttonValue == 'Google') {
            return $this->GoogleLogin($clientID, $clientSecret, $redirectURL, $errorMessage);
        }

    }

  public function GoogleLogin($clientID, $clientSecret, $redirectURL, $errorMessage) {
    global $session;
    require_once(DIR_SYSTEM . 'library/ocsociallogin/http.php');
    require_once(DIR_SYSTEM . 'library/ocsociallogin/oauth_client.php');

    $client = new oauth_client_class();
    $client->server       = 'Google';
    $client->offline      = true;              // refresh_token
    $client->debug        = true;
    $client->debug_http   = true;
    $client->redirect_uri = 'https://detta.com.ua/external-integrations/auth-google';

    $client->client_id     = $clientID;
    $client->client_secret = $clientSecret;

    if (!$client->client_id || !$client->client_secret) {
      $session->data['error'] = $errorMessage;
      return null;
    }

    // сучасні скоупи
    $client->scope = 'openid email profile';

    if (($success = $client->Initialize())) {
      $success = $client->Process();

      // ВАЖЛИВО: на першому проході клієнт може попросити завершити скрипт
      if ($client->exit) {
        $client->Finalize($success);
        exit;
      }

      if ($success) {
        if (strlen($client->authorization_error)) {
          $client->error = $client->authorization_error;
          $session->data['error'] = $client->authorization_error;
          $success = false;
        } elseif (strlen($client->access_token)) {
          // Отримати профіль користувача
          $success = $client->CallAPI(
            'https://www.googleapis.com/oauth2/v3/userinfo',
            'GET',
            array(),
            array('FailOnAccessError' => true),
            $user
          );
          if ($success) {
            $client->Finalize(true);
            return $user;
          }
        }
      } else {
        $session->data['error'] = $client->error;
      }

      $client->Finalize($success);
    }

    return null;
  }


  public function getEnabled() {

    }
}
