<?php
/**
 * BrForm
 *
 * ver 1.1
 */
class BrForm {

    /**
     * $fields = array(
     *      'name' => array(
     *          'display' => '表示名',
     *          'type' => 'input type', //text checkbox radio etc...
     *          'required' => true //bool,
     *          'default' => '初期値' //チェックボックスの場合は配列で指定
     *      )
     *  );
     */
    public $fields = array();

    //csv
    public $csv_delimiter = ',';
    public $csv_enclosure = '"';
    public $csv_to_encoding = 'sjis';
    public $csv_from_encoding = 'utf-8';

    //Api
    private $CusApiKey = null;
    private $CusApiSecret = null;

    function __construct()
    {

        session_start();

        date_default_timezone_set('Asia/Tokyo');

        //send mail param
        $this->To = null;
        $this->Subject = null;
        $this->Message = null;
        $this->From = null;
        $this->FromName = null;

        //csv
        $this->CsvFilePath = null;
    }

    /**
     * h
     *
     * @param string $s
     * @return string
     */
    public function h($s)
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    /**
     * setInputSessoin
     *
     * @return bool
     */
    public function setInputSessoin()
    {
        //set token
        $_SESSION['token'] = session_id();

        //set fields
        if (!empty($this->fields)) {
            foreach ($this->fields as $name => $field) {
                if (isset($field['type']) && $field['type'] == 'checkbox') {
                    $_SESSION[$name] = filter_input(INPUT_POST, $name, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
                    if (empty($_SESSION[$name]) && isset($field['default'])) {
                        $_SESSION[$name] = $field['default'];
                    }
                } else {
                    $_SESSION[$name] = filter_input(INPUT_POST, $name);
                    if ($_SESSION[$name] == null && isset($field['default'])) {
                        $_SESSION[$name] = $field['default'];
                    }
                }
            }
        }
        return true;
    }

    /**
     * out
     *
     * @param string $name
     * @param string $glue 値が配列の場合の連結文字
     * @return void
     */
    public function out($name = null, $glue = null)
    {
        if (empty($name)) {
            return null;
        }
        if (!empty($_SESSION[$name])) {
            $out = $_SESSION[$name];
        } else {
            if (isset($this->fields[$name]) && isset($this->fields[$name]['default'])) {
                $out = $this->fields[$name]['default'];
            }
        }

        if (!empty($out)) {
            if (isset($this->fields[$name]['type']) && $this->fields[$name]['type'] == 'checkbox') {
                if (!empty($glue)) {
                    return implode($glue, $out);
                }
                return $out;
            } else {
                return self::h($out);
            }
        }

        return null;
    }

    /**
     * checkRequired
     * 必須チェック
     *
     * @return bool
     */
    public function checkRequired()
    {
        if (empty($this->fields)) {
            return true;
        }
        $bool = true;
        foreach ($this->fields as $name => $field) {
            if (!empty($field['required'])) {
                if (isset($_SESSION[$name]) && $_SESSION[$name] == null) {
                    $bool = false;
                    break;
                }
            }
        }
        return $bool;
    }

    /**
     * checkToken
     * Tokenチェック
     *
     * @return bool
     */
    public function checkToken()
    {
        $bool = true;

        $token = filter_input(INPUT_POST, 'token');

        if (empty($token) || empty($_SESSION['token'])) {
            $bool = false;
        }

        if ($token != $_SESSION['token']) {
            $bool = false;
        }

        return $bool;
    }

    /**
     * redirect
     *
     * @param string $page
     * @return void
     */
    public function redirect($page = 'index.php?er=true')
    {
        header("Location: " . $page);
    }

    /**
     * sendEmail
     *
     * @return bool
     */
    public function sendEmail()
    {

        mb_language("Japanese");
        mb_internal_encoding("utf-8");

        try {

            if (empty($this->To)) {
                throw new Exception("Not Set Email", 1001);
            }

            if (empty($this->Subject)) {
                throw new Exception("Not Set Subject", 1002);
            }

            if (empty($this->Message)) {
                throw new Exception("Not Set Message", 1003);
            }

            if (empty($this->From)) {
                throw new Exception("Not Set From", 1004);
            }

            $header = 'From: ' . $this->From;
            if (isset($this->FromName)) {
                $header = 'From: ' .mb_encode_mimeheader($this->FromName) . '<' . $this->From . '>';
            }

            if (!mb_send_mail($this->To, $this->Subject, $this->Message, $header)) {
                throw new Exception("Send Error", 5001);
            }

        } catch (Exception $e) {
            throw new Exception($e);
            return false;
        }
        return true;
    }

    /**
     * getValue
     *
     * @param string $name
     * @param string $type
     * @return string
     */
    public function getValue($name = null, $type = null)
    {
        if (empty($name)) {
           return null;
        }
        $value = null;
        if (isset($_SESSION[$name])) {
            $value = $_SESSION[$name];
            if ($type == 'checkbox') {
                $value = implode(',', $value);
            }
        }
        return $value;
    } 

    /**
     * addCsvRow
     *
     * @return bool
     */
    public function addCsvRow()
    {
        if (empty($this->CsvFilePath)) {
            return false;
        }

        if (empty($this->fields)) {
            return false;
        }

        //set values
        $row = array(date('Y-m-d H:i:s'));
        foreach ($this->fields as $name => $field) {
            $type = isset($field['type'])? $field['type'] : null;
            $value = self::getValue($name, $type);
            if (!empty($value)) {
                $value = mb_convert_encoding($value, $this->csv_to_encoding, $this->csv_from_encoding);
                $value = str_replace(array("\r\n", "\r", "\n"), '', $value);
            }
            $row[] = $value;
        }

        //add row
        $fp = fopen($this->CsvFilePath, 'a');

        if (!file_exists($this->CsvFilePath)) {
            return false;
        }

        fputcsv($fp, $row, $this->csv_delimiter, $this->csv_enclosure);

        fclose($fp);

        return true;
    }

    /**
     * setCusApiKey
     *
     * @param string $key
     * @return bool
     */
    public function setCusApiKey($key = null)
    {
        $this->ApiKey = $key;
        return true;
    }

    /**
     * setCusApiSecret
     *
     * @param string $secret
     * @return bool
     */
    public function setCusApiSecret($secret = null)
    {
        $this->ApiSecret = $secret;
        return true;
    }

    /**
     * postCusApi
     *
     * @param string $url
     * @return array
     */
    public function postCusApi($url = null)
    {
        
        //set data
        foreach ($this->fields as $name => $field) {
            $type = isset($field['type'])? $field['type'] : null;
            $value = self::getValue($name, $type);
            $data[$name] = $value;
        }

        /**
         * Set Post Info
         */

        //操作IPアドレス
        $data['REMOTE_ADDR'] = empty($_SERVER['REMOTE_ADDR'])? null : $_SERVER['REMOTE_ADDR'];

        //操作時URL
        $http = empty($_SERVER["HTTPS"])? "http://" : "https://";
        $HTTP_HOST = empty($_SERVER["HTTP_HOST"])? null : $_SERVER["HTTP_HOST"];
        $REQUEST_URI = empty($_SERVER["REQUEST_URI"])? null : $_SERVER["REQUEST_URI"];
        $data['URL'] = $http . $HTTP_HOST . $REQUEST_URI;

        //リファラー
        $data['HTTP_REFERER'] = empty($_SERVER['HTTP_REFERER'])? null : $_SERVER['HTTP_REFERER'];

        //HTTP_USER_AGENT
        $data['HTTP_USER_AGENT'] = empty($_SERVER['HTTP_USER_AGENT'])? null : $_SERVER['HTTP_USER_AGENT'];

        //サーバーIPアドレス
        $data['SERVER_ADDR'] = empty($_SERVER['SERVER_ADDR'])? null : $_SERVER['SERVER_ADDR'];

        /**
         * Set Url
         */
        if (!empty($url)) {
            $quey = array(
                'key' => $this->ApiKey,
                'secret' => $this->ApiSecret
            );
            $url = $url . '?' . http_build_query($quey);
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        //multipartリクエストを許可していないサーバの場合
        // curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        $responce_body = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        return array(
            'status_code' => $status_code,
            'responce_body' => $responce_body
        );
    }

    /**
     * end
     *
     * @return bool
     */
    public function end()
    {
        session_destroy();
        return true;
    }
}
?>