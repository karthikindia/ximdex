<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 19/02/16
 * Time: 14:39
 */

namespace Ximdex\API;


class Response
{
    private $status = 0;
    private $response = null;
    private $message = '';

    public function __construct(){

    }

    /**
     * Sets the status code
     *
     * @param $status
     * @return $this
     */
    public function setStatus($status){
        $this->status = $status;
        return $this;
    }

    /**
     * Sets the message
     *
     * @param $message
     * @return $this
     */
    public function setMessage($message){
        $this->message = $message;
        return $this;
    }

    /**
     * Sets the response
     *
     * @param $response
     * @return $this
     */
    public function setResponse($response){
        $this->response = $response;
        return $this;
    }

    /**
     * Renders the response
     *
     * @param string $method
     * @return string
     */
    public function render($method = 'JSON'){
        $data = [
          'status' => $this->status,
          'message' => $this->message,
          'response' => $this->response,
        ];

        switch($method){
            case 'JSON':
                header('Content-Type: application/json; charset=utf-8');
                return json_encode($data, JSON_UNESCAPED_UNICODE);
            default:
                header('Content-Type: application/json; charset=utf-8');
                return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }
}