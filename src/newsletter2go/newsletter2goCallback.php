<?php


class Newsletter2GoCallback
{
    private static $instance = null;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        return self::$instance ? : new Newsletter2GoCallback();
    }

    public function processCallback($postParams)
    {
        if (isset($postParams['auth_key']) && !empty($postParams['auth_key'])) {
            $authKey = $postParams['auth_key'];
            variable_set('newsletter2go_authKey', $authKey.':foo');
        }        
        if (isset($postParams['access_token']) && !empty($postParams['access_token'])) {
            $accessToken = $postParams['access_token'];
            variable_set('newsletter2go_accessToken', $accessToken);
        }       
        if (isset($postParams['refresh_token']) && !empty($postParams['refresh_token'])) {
            $refreshToken = $postParams['refresh_token'];
            variable_set('newsletter2go_refreshToken', $refreshToken);
        }

        $result = array('success' => 1);
        drupal_json_output($result);
    }
}
