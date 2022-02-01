<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

trait PluginServicesFagoUtils {

    public static function getSearchOptions($type, $field){

        if (!isset($type)) {
            help();
            die("** mandatory option 'type' is missing\n");
        }
        if (!class_exists($type)) {
        die("** unknown type\n");
        }
        
        $opts = &Search::getOptions($type);
        $sort = [];
        $group = 'N/A';
        foreach ($opts as $ref => $opt) {
            if (isset($opt['field'])) {
            $sort[$ref] = $group . " / " . $opt['name'];
            } else {
            if (is_array($opt)) {
                $group = $opt['name'];
            } else {
                $group = $opt;
            }
            }
        }
        return $sort[$field];
    }

    /**
    * This function is used to return the className of an specific item by his plugin
    *
    * @return an itemtype
    */
    public static function getSubItemForItemType($itemtype){
        $plug = new Plugin();
        $list_plug = $plug->find();
        foreach($list_plug as $values){
            $val = class_exists("Plugin".ucfirst($values['directory']).$itemtype) ? "Plugin".ucfirst($values['directory']).$itemtype: $itemtype;
            return $val;
        }
        return false;
    }

    /**
    * Generic messages
    *
    * @since 9.1
    *
    * @param mixed   $response          string message or array of data to send
    * @param integer $httpcode          http code (see : https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
    * @param array   $additionalheaders headers to send with http response (must be an array(key => value))
    *
    * @return void
    */
    public static function returnResponse($response = "", $httpcode = 200, $additionalheaders = []) {
        $message = [];

        if (empty($httpcode)) {
            $httpcode = 200;
        }

        foreach ($additionalheaders as $key => $value) {
            header("$key: $value");
        }

        http_response_code($httpcode);

        if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])
        && count($_SESSION["MESSAGE_AFTER_REDIRECT"]) > 0) {
            foreach ($_SESSION['MESSAGE_AFTER_REDIRECT'] as $msgtype => $messages) {
                  //get messages
                if (count($messages) > 0) {
                    $html_messages = implode('<br/>', $messages);
                } else {
                    continue;
                }
                //set title and css class
                switch ($msgtype) {
                    case ERROR:
                        $message['error'] = $html_messages;
                        break;
                    case WARNING:
                        $message['warning'] = $html_messages;
                        break;
                    case INFO:
                        $message['info'] = $html_messages;
                        break;
                }
            }
            // Clean message
            $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
        }

        echo json_encode([
            "message" => $message,
            "response" => $response
        ]);
        exit;
    }


    /**
    * Generic function to send a error message and an error code to client
    *
    * @param string  $message         message to send (human readable)(default 'Bad Request')
    * @param integer $httpcode        http code (see : https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
    *                                      (default 400)
    * @param string  $statuscode      API status (to represent more precisely the current error)
    * @param boolean $return_response if true, the error will be send to returnResponse function
    *                                      (who may exit after sending data), otherwise,
    *                                      we will return an array with the error
    *                                      (default true)
    *
    * @return array
    */
    public  static function returnError($message = "Bad Request", $httpcode = 400, $statuscode = "ERROR", $return_response = true) {

        if (empty($httpcode)) {
            $httpcode = 400;
        }
        
        if (empty($statuscode)) {
            $statuscode = "ERROR";
        }


        if ($return_response) {
            // return $this->returnResponse([$statuscode, $message], $httpcode);
        }
        return [$statuscode, $message];
    }

}