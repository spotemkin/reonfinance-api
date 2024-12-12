<?php

class TelegramBotMessage{

    private $method = 'sendMessage';
    private $data;
    private $headers;
    private $token;

    function getMethod(){
        return $this->method;
    }
    function getData(){
        return $this->data;
    }
    function getHeaders(){
        return $this->headers;
    }
    function getToken(){
        return $this->token;
    }

    function getPhone($chat_id) // request user phone
    {
        $this->setData(array(
            'text' => 'Please get access to view you phone number',
            'chat_id' => $chat_id,
            'reply_markup' => array(
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
                'keyboard' => array(
                    array(
                        array(
                            'text' => 'Send my phone number',
                            'request_contact' => true,
                        ),
                    )
                )
            )
        ));
    }

    function getLocation($chat_id) // Request user location
    {
        $this->setMethod('sendMessage');
        $this->setData(array(
            'text' => 'Please get access to you location',
            'chat_id' => $chat_id,
            'reply_markup' => array(
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
                'keyboard' => array(
                    array(
                        array(
                            'text' => 'Send my location',
                            'request_location' => true,
                        ),
                    )
                )
            )
        ));
    }

    function setMethod(string $method){
        $this->method = $method;
    }
    function setData($data){
        $this->data = $data;
    }
    function setToken(string $token){
        $this->token = $token;
    }
    function setHeaders($headers){
        $this->headers = $headers;
    }

    function send(){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->getToken() . '/' . $this->getMethod(),
            CURLOPT_POSTFIELDS => json_encode($this->getData()),
            CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"), $this->getHeaders())
        ));

        $result = curl_exec($curl);
        curl_close($curl);

        return (json_decode($result, 1) ? json_decode($result, 1) : json_decode($result));
    }

    public function __construct($method_ = 'sendMessage', $data_ = array(), $headers_ = array())
    {
        $this->method = $method_;
        $this->data = $data_;
        $this->headers = $headers_;
        $this->token = getenv('BOT_TOKEN');
    }
}

class TelegramBotCallbackData{

    private $chatId;
    private $messageText = '';
    private $command = false;
    private $userLogin = '';
    private $userId = '';
    private $userLanguage = '';
    private $userName = '';
    private $messageId = '';
    private $payment = false;
    private $paymentId = 0;
    private $callback = 0;

    public function __construct($data)
    {
        if (
            (key_exists('message', $data)) and
            (key_exists('chat', $data['message'])) and
            (key_exists('id', $data['message']['chat']))
        )
        {
            $this->userLogin = $data['message']['chat']['username'] ?? '';
            $this->chatId = $data['message']['chat']['id'];

            if (
                (key_exists('from', $data['message'])) and
                (key_exists('id', $data['message']['from']))
            )
                $this->userId = $data['message']['from']['id'];

            if (
                (key_exists('from', $data['message'])) and
                (key_exists('language_code', $data['message']['from']))
            )
                $this->userLanguage = $data['message']['from']['language_code'];

            if (
                (key_exists('from', $data['message'])) and
                (key_exists('first_name', $data['message']['from']))
            )
                $this->userName = $data['message']['from']['first_name'];

            if (
                (key_exists('text', $data['message']))
            )
                $this->messageText = $data['message']['text'];

            if (
                (key_exists('message_id', $data['message']))
            )
                $this->messageId = $data['message']['message_id'];

        }
        elseif (
            (key_exists('callback_query', $data)) and
            (key_exists('message', $data['callback_query'])) and
            (key_exists('chat', $data['callback_query']['message'])) and
            (key_exists('id', $data['callback_query']['message']['chat']))
        )
        {
            $this->userLogin = $data['callback_query']['message']['chat']['username'] ?? '';
            $this->chatId = $data['callback_query']['message']['chat']['id'];
            $this->callback = true;

            if (
                (key_exists('from', $data['callback_query'])) and
                (key_exists('id', $data['callback_query']['from']))
            )
                $this->userId = $data['callback_query']['from']['id'];

            if (
                (key_exists('from', $data['callback_query'])) and
                (key_exists('first_name', $data['callback_query']['from']))
            )
                $this->userName = $data['callback_query']['from']['first_name'];

            if (
                (key_exists('data', $data['callback_query']))
            )
                $message_text = $data['callback_query']['data'];

            if (
                (key_exists('message', $data['callback_query'])) and
                (key_exists('message_id', $data['callback_query']['message']))
            )
                $message_id = $data['callback_query']['message']['message_id'];
        }
        elseif (
            (key_exists('pre_checkout_query', $data)) and
            (key_exists('id', $data['pre_checkout_query'])) and
            (key_exists('invoice_payload', $data['pre_checkout_query'])) and
            (key_exists('from', $data['pre_checkout_query'])) and
            (key_exists('id', $data['pre_checkout_query']['from'])) and
            (key_exists('total_amount', $data['pre_checkout_query']))
        ){
            $message_type = 'pre_checkout_query';
            $payment_id = $data['pre_checkout_query']['invoice_payload'];
            $chat_id = $user_id = $data['pre_checkout_query']['from']['id'];
            $amount = $data['pre_checkout_query']['total_amount'];
            $pre_checkout_query_id = $data['pre_checkout_query']['id'];
        }
    }

}