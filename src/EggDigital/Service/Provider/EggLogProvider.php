<?php

namespace EggDigital\Service\Provider;

class EggLogProvider
{
    protected $_activity_log_threshold = 3;
    protected $_enabled                = TRUE;
    protected $_date_fmt               = 'Y-m-d H:i:s';
    protected $_messageDelimeter       = ' --> ';
    protected $_prefixLogFile          = 'activity_log-';
    protected $_extensionTxt           = '.txt';
    protected $_extensionJson          = '.json';
    protected $_extensionPhp           = '.php';
    protected $_levels                 = array('ERROR' => '1', 'INFO' => '2', 'ALL' => '3');
    protected $_activity_type          = array(
        'ERROR'              => 'ERROR',
        'TEST_ACTIVITY_TYPE' => 'INFO'
    );
    private $_column_delimiter = '<>';
    protected $_log_path;
    private $replacements = 'xxx';

    protected $APIIN   = "api_in";
    protected $APIOUT  = "api_out";
    protected $CURLIN  = "curl_req";
    protected $CURLOUT = "curl_res";
    protected $WEBIN   = "web_in";
    protected $TEXT    = "text";

    protected $TEXT_DESC_LIST = ['action_type', 'file_size', 'short_code', 'file_type', 'file_name'];

    protected $reqType = array(
            "apiIn"     => array(
                            "name" => $this->APIIN,
                            "mode" => "IN",
                            ),
            "apiOut"    => array(
                            "name" => $this->APIOUT,
                            "mode" => "OUT",
                            ),
            "curlIn"    => array(
                            "name" => $this->CURLIN,
                            "mode" => "IN",
                            ),
            "curlOut"   => array(
                            "name" => $this->CURLOUT,
                            "mode" => "OUT",
                            ),
            "webIn"     => array(
                            "name" => $this->WEBIN,
                            "mode" => "IN",
                            ),
            "text"      => array(
                            "name" => $this->TEXT,
                            "mode" => "TEXT",
                            )
        );

    /**
     * Constructor
     */
    public function __construct()
    {
        try {
            $config = \Config::get('config');

            if (empty($config['LOG_PATH'])) {
                throw new \Exception('Not config log path');
            } else {
                $this->_log_path = $config['LOG_PATH'];
            }

            if ( ! defined('__LOG_DELIMITER__')) {
                throw new \Exception('Not defined __LOG_DELIMITER__');
            }

            if (is_numeric(@$config['activity_log_threshold'])) {
                $this->_activity_log_threshold = $config['activity_log_threshold'];
            }
        } catch (\Exception $e) {
            //var_dump($e->getMessage());
            throw new \Exception($e->getMessage());
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

    private function _validateMessage($message)
    {
        if (empty(trim($message))) {
            return FALSE;
        }

        if ($this->_enabled === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    private function _format_message($message)
    {
        //validate message
        $isValid = self::_validateMessage($message);

        if (!$isValid) {
            return $isValid;
        }

        $a_message = explode(__LOG_DELIMITER__, $message);

        foreach ($a_message as $m) {
            $a_msg[] = $m;
        }

        $msg   = implode($this->_column_delimiter, $a_msg);

        return $msg;
    }

    private function _format_level($level)
    {
        $level = strtoupper($level);

        if ( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_activity_log_threshold)) {
            return FALSE;
        }

        return $level;
    }

    private function _createFilePath($extension) 
    {
        return $this->_log_path . $this->_prefixLogFile . date('Y-m-d') . $extension;
    }

    private function _createMsg($level, $msg)
    {
        $level_txt = (($level == 'INFO') ? $level.'  -' : $level.' -');
        
        $message = $level_txt.' '.date($this->_date_fmt). $this->_messageDelimeter . $msg . "\n\n";
        return $message;
    }

    // private function _addLogToFile($filepath, $message)
    // {
    //     try {
    //         if ( ! $fp = @fopen($filepath, 'ab')) {
    //             // return FALSE;
    //             throw new \Exception('Error fopen');
    //         }
            
    //         flock($fp, LOCK_EX);
    //         fwrite($fp, $message);
    //         flock($fp, LOCK_UN);
    //         fclose($fp);

    //         @chmod($filepath, 0666);
    //     } catch (\Exception $e) {
    //         throw new \Exception($e->getMessage());
    //     }
    // }

    private function _write_log($level, $message)
    {
        // check message and change format 
        $msg = self::_format_message($message);

        if (!$msg){
            return FALSE;
        }

        // check level and change format 
        $level = self::_format_level($level);

        if (!$level){
            return FALSE;
        }
        
        //define variable
        $message  = self::_createMsg($level, $msg);
        $filepath = self::_createFilePath($this->_extensionTxt);
        
        //self::_addLogToFile($filepath, $message);
        self::writeLog($filepath, $message);
        return TRUE;
    }

    /************************************************************
    *************************************************************/

    public function writeLogJson($message)
    {
        //validate message
        $isValid = self::_validateMessage($message);

        if (!$isValid) {
            return $isValid;
        }

        //create message
        $messageJson = json_encode($message, JSON_FORCE_OBJECT) . "\n\n";

        //create log file path
        $filepathJson = self::_createFilePath($this->_extensionJson); 
        //write log to file
        self::writeLog($filepathJson, $messageJson);

        return TRUE;
    }

    private function writeLog($filepath, $message)
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
            //var_dump($e->getMessage());
            throw new \Exception($e->getMessage());
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

    /*****************************************************
     * Service write log
     *****************************************************/

    /* ===================
    *  Prepare parameter 
    *  =================== */
    private function replacementsParams($requestInput)
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

    private function _formatParam($params, $mode="IN")
    {
        if (!isset($params['description'])) {
            $params['description'] = "";
        } 

        //some type use params for requestInput
        if (!isset($params['requestInput']))
        {
            if (isset($params['params'])){
                $params['requestInput'] = $params['params'];
            }
        }

        if (!is_array($params['requestInput'])) {
            $params['requestInput'] = array($params['requestInput']);
        } 

        if (is_array($params['description'])) {
            $params['descriptions'] = array($params['description']);
            //clear discription
            $params['description']  = "";
        }

        if ((isset($params['descriptions'])) && (!is_array($params['descriptions']))) {
            $params['descriptions'] = array($params['descriptions']);
        }

        //Replacements Params
        $params['requestInput'] = $this->replacementsParams($params['requestInput']);

        if ($mode == "OUT");
        {
            if (!is_array($params['return_data'])) {
                $params['return_data'] = array($params['return_data']);
            }

        } else if ($mode == "TEXT") {
            $descriptions = array();
            foreach ($params['descriptions'] as $key => $value) {
                if (in_array($key, $this->TEXT_DESC_LIST)) {
                    $descriptions[$key] = $value;
                }
            }
        }



        return $params;
    }

    private function _createResponseTime($start)
    {
        $end            = microtime(true);
        $response_time  = (float) number_format(($end - $start), 2);
        return $response_time;
    }

    private function _createParameter($param, $reqObj)
    {
        //format input param
        $params = self::_formatParam($params, $reqObj["mode"]);

        //api_in
        $parameters = array(
            // Common Field
            'transaction_id' => $params['transaction_id'], // Require
            'datetime'       => $this->udate('Y-m-d H:i:s.u'), // Require
            'filename'       => $params['action']['route_controller'] . $this->_extensionPhp, // Require
            'class'          => $params['action']['route_controller'],
            'function'       => $params['action']['route_method'],
            'level'          => $params['info'], // Require
            'environment'    => $params['config']['ENVIRONMENT'], // Require
            'description'    => $params["description"],
            'log_type'       => $reqObj["name"],
            'url'            => $params['requestFullUrl'],
            'param'          => json_encode($params["requestInput"], JSON_FORCE_OBJECT)
        );

        if (($reqObj["name"] == $this->APIIN) || ($reqObj["name"] == $this->WEBIN)) {
            $parameters["method"]     = $params['action']['route_method'];
            $parameters["ip"]         = $params['ip'];
            $parameters["caller_ip"]  = $params['caller_ip'];
            $parameters["controller"] = $params['action']['route_controller'];

        } else if ($reqObj["name"] == $this->APIOUT){
            $parameters["return_data"]   = json_encode($parameters["return_data"], JSON_FORCE_OBJECT);
            $parameters["return_status"] = $params['return_status'];
            $parameters["response_time"] = self::_createResponseTime($params['start']);
            $parameters["return_code"]   = (string) $params['return_code'];
            $parameters["controller"]    = $params['action']['route_controller'];

        } else if ($reqObj["name"] == $this->CURLIN) {
            $parameters["curl_method"] = $params['service'];
            $parameters["ip"]          = $params['ip'];
            $parameters["caller_ip"]   = $params['caller_ip'];

        } else if($reqObj["name"] == $this->CURLOUT) {
            $parameters["curl_method"]   = $params['service'];
            $parameters["ip"]            = $params['ip'];
            $parameters["caller_ip"]     = $params['caller_ip'];
            $parameters["return_data"]   = json_encode($parameters["return_data"], JSON_FORCE_OBJECT);
            $parameters["return_status"] = $params['return_status'];
            $parameters["response_time"] = self::_createResponseTime($params['start']);
            $parameters["return_code"]   = (string) $params['return_code'];

        } else if($resObj["name"] == $this->TEXT) {
            $parameters["method"]       = $params['action']['route_method'];
            $parameters["ip"]           = $params['ip'];
            $parameters["caller_ip"]    = $params['caller_ip'];
            $parameters["controller"]   = $params['action']['route_controller'];
            $parameters["descriptions"] = $params['descriptions'];
        }

    }


    public function logApiIn(array $params)
    {
        $data = self::_createParameter($param, $this->reqType["apiIn"]);
        
        self::writeLogJson($data);
    }

    public function logApiOut(array $params)
    {
        $data = self::_createParameter($param, $this->reqType["apiOut"]);
        
        self::writeLogJson($data);
    }

    public function logWebIn(array $params)
    {
        $data = self::_createParameter($param, $this->reqType["webIn"]);
        
        self::writeLogJson($data);
    }

    public function logCurlIn(array $params)
    {
        $data = self::_createParameter($param, $this->reqType["curlIn"]);
        
        self::writeLogJson($data);
    }

    public function logCurlOut(array $params)
    {
        $data = self::_createParameter($param, $this->reqType["curlOut"]);
        
        self::writeLogJson($data);
    }

    public function logText(array $params)
    {
        $data = self::_createParameter($param, $this->reqType["text"]);
        
        self::writeLogJson($data);
    }

    
}