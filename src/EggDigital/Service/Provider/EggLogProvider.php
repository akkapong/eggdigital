<?php

namespace EggDigital\Service\Provider;

class EggLogProvider
{
    protected $_activity_log_threshold = 3;
    protected $_enabled                = TRUE;
    protected $_date_fmt               = 'Y-m-d H:i:s';
    protected $_levels                 = array('ERROR' => '1', 'INFO' => '2', 'ALL' => '3');
    protected $_activity_type          = array(
        'ERROR'              => 'ERROR',
        'TEST_ACTIVITY_TYPE' => 'INFO'
    );
    private $_column_delimiter = '<>';
    protected $_log_path;
    private $replacements = 'xxx';

    /**
     * Constructor
     */
    public function __construct()
    {
        try {
            $config = \Config::get('config');

            if (empty($config['LOG_PATH'])) {
                throw new \Exception('Not config log path');
            }

            if ( ! defined('__LOG_DELIMITER__')) {
                throw new \Exception('Not defined __LOG_DELIMITER__');
            }

            if ($config['LOG_PATH'] != '') {
                $this->_log_path = $config['LOG_PATH'];
            } else {
                $this->_log_path = storage_path() . "\logs\\";
            }

            if (is_numeric(@$config['activity_log_threshold'])) {
                $this->_activity_log_threshold = $config['activity_log_threshold'];
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

    public function activity_log($activity, $message)
    {
        if ( array_key_exists($activity, $this->_activity_type)) {
            $level = $this->_activity_type[$activity];
        } else {
            return FALSE;
        }

        return $this->_write_log($level, $message);
    }

    private function _write_log($level, $message)
    {
        if (trim($message) == '') {
            return FALSE;
        }

        if ($this->_enabled === FALSE) {
            return FALSE;
        }

        $a_message = explode(__LOG_DELIMITER__, $message);

        foreach ($a_message as $m) {
            $a_msg[] = $m;
        }

        $msg   = implode($this->_column_delimiter, $a_msg);
        $level = strtoupper($level);

        if ( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_activity_log_threshold)) {
            return FALSE;
        }

        $filepath = $this->_log_path . 'activity_log-'.date('Y-m-d').'.txt';
        $message  = '';

        /*if ( ! file_exists($filepath)) {
            $message .= "<"."?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?".">\n\n";
        }*/

        if ( ! $fp = @fopen($filepath, 'ab')) {
            // return FALSE;
            throw new \Exception('Error fopen');
        }

        $message .= $level.' '.(($level == 'INFO') ? ' -' : '-').' '.date($this->_date_fmt). ' --> '.$msg."\n\n";

        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        @chmod($filepath, 0666);

        return TRUE;
    }

    /************************************************************
    *************************************************************
    *************************************************************
    *************************************************************
    *************************************************************/

    public function writeLogJson($message)
    {
        if (empty($message)) {
            return false;
        }

        if ($this->_enabled === FALSE) {
            return FALSE;
        }

        $messageJson  = '';
        $messageJson .= json_encode($message, JSON_FORCE_OBJECT) . "\n\n";

        $filepathJson = $this->_log_path . 'activity_log-' . date('Y-m-d') . '.json';

        self::writeLog($filepathJson, $messageJson);

        return TRUE;
    }

    protected function writeLog($filepath, $message)
    {
        try {
            $resultPutContent = file_put_contents($filepath, $message, FILE_APPEND);

            if ($resultPutContent === false) {
                $result = false;
            } else {
                $result = true;
            }

            return $result;
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

    public function udate($format, $utimestamp = null)
    {
        if (is_null($utimestamp)) {
            $utimestamp = microtime(true);
        }

        $timestamp    = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);

        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }

    /**
     * Service write log
     */
    public function logApiIn(array $params)
    {
        if (isset($params['description'])) {
            $description = $params['description'];
        } else {
            $description = '';
        }

        if (is_array($params['requestInput'])) {
            $requestInput = $params['requestInput'];
        } else {
            $requestInput = array($params['requestInput']);
        }

        //Replacements Params
        $requestInput = $this->replacementsParams($requestInput);

        $data = array(
            // Common Field
            'transaction_id' => $params['transaction_id'], // Require
            //'datetime'       => date('Y-m-d H:i:s').' '.microtime(true), // Require
            'datetime'       => $this->udate('Y-m-d H:i:s.u'), // Require
            'filename'       => $params['action']['route_controller'] . '.php', // Require
            'class'          => $params['action']['route_controller'],
            // 'function'    => __FUNCTION__,
            'function'       => $params['action']['route_method'],
            'level'          => $params['info'], // Require
            'environment'    => $params['config']['ENVIRONMENT'], // Require
            'description'    => $description,
            'log_type'       => 'api_in',
            'url'            => $params['requestFullUrl'],
            'param'          => json_encode($requestInput, JSON_FORCE_OBJECT),
            'controller'     => $params['action']['route_controller'],
            'method'         => $params['action']['route_method'],
            'ip'             => $params['ip'], // if host use $params['requestHost']['host'][0]
            'caller_ip'      => $params['caller_ip'],
        );

        if ( ! isset($params['description'])) {
            unset($data['description']);
        }

        self::writeLogJson($data);
    }

    public function logApiOut(array $params)
    {
        $end            = microtime(true);
        $response_time  = number_format(($end - $params['start']), 2);

        if (isset($params['description'])) {
            $description = $params['description'];
        } else {
            $description = '';
        }

        if (is_array($params['requestInput'])) {
            $requestInput = $params['requestInput'];
        } else {
            $requestInput = array($params['requestInput']);
        }

        if (is_array($params['return_data'])) {
            $return_data = $params['return_data'];
        } else {
            $return_data = array($params['return_data']);
        }

        //Replacements Params
        $requestInput = $this->replacementsParams($requestInput);

        $data = array(
            // Common Field
            'transaction_id' => $params['transaction_id'], // Require
            //'datetime'       => date('Y-m-d H:i:s').' '.microtime(true), // Require
            'datetime'       => $this->udate('Y-m-d H:i:s.u'), // Require
            'filename'       => $params['action']['route_controller'] . '.php', // Require
            'class'          => $params['action']['route_controller'],
            'function'       => $params['action']['route_method'],
            'level'          => $params['info'], // Require
            'environment'    => $params['config']['ENVIRONMENT'], // Require
            'description'    => $description,
            'log_type'       => 'api_out',
            'url'            => $params['requestFullUrl'],
            'param'          => json_encode($requestInput, JSON_FORCE_OBJECT),
            'controller'     => $params['action']['route_controller'],
            'return_data'    => json_encode($return_data, JSON_FORCE_OBJECT),
            'return_status'  => $params['return_status'],
            'response_time'  => (float) $response_time,
            'return_code'    => (string) $params['return_code'],
        );

        if ( ! isset($params['description'])) {
            unset($data['description']);
        }

        self::writeLogJson($data);
    }

    public function logWebIn(array $params)
    {
        if (isset($params['description'])) {
            $description = $params['description'];
        } else {
            $description = '';
        }

        if (is_array($params['requestInput'])) {
            $requestInput = $params['requestInput'];
        } else {
            $requestInput = array($params['requestInput']);
        }

        //Replacements Params
        $requestInput = $this->replacementsParams($requestInput);

        $data = array(
            // Common Field
            'transaction_id' => $params['transaction_id'], // Require
            //'datetime'       => date('Y-m-d H:i:s').' '.microtime(true), // Require
            'datetime'       => $this->udate('Y-m-d H:i:s.u'), // Require
            'filename'       => $params['action']['route_controller'] . '.php', // Require
            'class'          => $params['action']['route_controller'],
            // 'function'       => __FUNCTION__,
            'function'       => $params['action']['route_method'],
            'level'          => $params['info'], // Require
            'environment'    => $params['config']['ENVIRONMENT'], // Require
            'description'    => $description,
            'log_type'       => 'web_in',
            'url'            => $params['requestFullUrl'],
            'param'          => json_encode($requestInput, JSON_FORCE_OBJECT),
            'controller'     => $params['action']['route_controller'],
            'method'         => $params['action']['route_method'],
            'ip'             => $params['ip'], // if host use $params['requestHost']['host'][0]
            'caller_ip'      => $params['caller_ip'],
        );

        if ( ! isset($params['description'])) {
            unset($data['description']);
        }

        self::writeLogJson($data);
    }

    public function logCurlIn(array $params)
    {
        if ( ! isset($params['description'])) {
            $params['description'] = '';
        }

        if (is_array($params['params'])) {
            $curlParams = $params['params'];
        } else {
            $curlParams = array($params['params']);
        }

        //Replacements Params
        $curlParams = $this->replacementsParams($curlParams);

        $data = array(
            // Common Field
            'transaction_id' => $params['transaction_id'], // Require
            //'datetime'       => date('Y-m-d H:i:s').' '.microtime(true), // Require
            'datetime'       => $this->udate('Y-m-d H:i:s.u'), // Require
            'filename'       => $params['action']['route_controller'] . '.php', // Require
            'class'          => $params['action']['route_controller'],
            'function'       => $params['action']['route_method'],
            'level'          => $params['info'], // Require
            'environment'    => $params['config']['ENVIRONMENT'], // Require
            'description'    => $params['description'],
            'log_type'       => 'curl_req',
            'url'            => $params['url'],
            'param'          => json_encode($curlParams),
            'curl_method'    => $params['service'],
            'ip'             => $params['ip'], // if host use $params['requestHost']['host'][0]
            'caller_ip'      => $params['caller_ip'],
        );

        if ( ! isset($params['description'])) {
            unset($data['description']);
        }

        self::writeLogJson($data);
    }

    public function logCurlOut(array $params)
    {
        $end            = microtime(true);
        $response_time  = number_format(($end - $params['start']), 2);

        if ( ! isset($params['description'])) {
            $params['description'] = '';
        }

        if (is_array($params['params'])) {
            $curlParams = $params['params'];
        } else {
            $curlParams = array($params['params']);
        }

        if (is_array($params['return_data'])) {
            $return_data = $params['return_data'];
        } else {
            $return_data = array($params['return_data']);
        }

        //Replacements Params
        $curlParams = $this->replacementsParams($curlParams);

        $data = array(
            // Common Field
            'transaction_id' => $params['transaction_id'], // Require
            //'datetime'       => date('Y-m-d H:i:s').' '.microtime(true), // Require
            'datetime'       => $this->udate('Y-m-d H:i:s.u'), // Require
            'filename'       => $params['action']['route_controller'] . '.php', // Require
            'class'          => $params['action']['route_controller'],
            'function'       => $params['action']['route_method'],
            'level'          => $params['info'], // Require
            'environment'    => $params['config']['ENVIRONMENT'], // Require
            'description'    => $params['description'],
            'log_type'       => 'curl_res',
            'url'            => $params['url'],
            'param'          => json_encode($curlParams),
            'curl_method'    => $params['service'],
            'ip'             => $params['ip'], // if host use $params['requestHost']['host'][0]
            'caller_ip'      => $params['caller_ip'],
            'return_data'    => json_encode($return_data, JSON_FORCE_OBJECT),
            'return_status'  => $params['return_status'],
            'response_time'  => (float) $response_time,
            'return_code'    => (string) $params['return_code'],
        );

        if ( ! isset($params['description'])) {
            unset($data['description']);
        }

        self::writeLogJson($data);
    }

    protected function replacementsParams($requestInput)
    {
        if (isset($requestInput['apiKey'])) {
            $requestInput['apiKey'] = substr_replace($requestInput['apiKey'], $this->replacements, 5, -5);
        }

        if (isset($requestInput['password'])) {
            $requestInput['password'] = $this->replacements;
        }

        if (isset($requestInput['pwd'])) {
            $requestInput['pwd'] = $this->replacements;
        }

        return $requestInput;
    }
}