<?php

namespace EggDigital\Service\Provider;

class PcsClient {

    protected $user_id;
    protected $service;
    protected $pcs_url;

    private $uri_authorization = 'authorization';
    private $uri_getUserPermission = 'getUserPermission';

    public function __construct($params=array()) {
        if( isset($params['url']))      $this->setPcsUrl($params['url']);
        if( isset($params['user_id']))  $this->setUserId($params['user_id']);
        if( isset($params['service']))  $this->setService($params['service']);
    }

    public function setPcsUrl($pcs_url) {
        $this->pcs_url = $pcs_url;
    }

    public function setUserId($user_id) {
        $this->user_id = $user_id;
    }

    public function setService($service) {
        $this->service = $service;
    }

    public function checkPermission($permission = '' ,$role_position = '') {

        if (empty($this->user_id) OR empty($permission)) {
            return false;
        }

        $service = !empty($this->service) ? $this->service . "@" : '' ;

        $params                  = array();
        $params["user_id"]       = $this->user_id;
        $params["permission"]    = $service . $permission;
        $params["role_position"] = $role_position;

        $result = $this->apiCall($this->uri_authorization, $params);

        $check = false;
        if (isset($result->status)) {
            $check = $result->status;
        }

        return $check;
    }

    public function getAllPermission($service='', $sso_id='') {

        $params = array();
        $params["user_id"] = ($sso_id) ? $sso_id : $this->user_id ;
        $params["service"] = ($service) ? $service : $this->service ;

        $result = $this->apiCall($this->uri_getUserPermission, $params);

        $data = array();
        if (isset($result->status) and $result->status == 200) {
            $data = @$result->data;
        }

        return $data;
    }

    private function apiCall($uri, array $params = array()) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->pcs_url . $uri);
        curl_setopt($ch, CURLOPT_POST, 1);

        if (!empty($params)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, 
                     http_build_query($params));
        }
        // receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $server_output = curl_exec($ch);

        curl_close($ch);

        return json_decode($server_output);
    }

}