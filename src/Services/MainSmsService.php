<?php


namespace Grechanyuk\MainSms\Services;


class MainSmsService
{
    const REQUEST_SUCCESS = 'success';
    const REQUEST_ERROR = 'error';

    protected
        $project    = null,
        $key        = null,
        $testMode   = false,
        $url        = 'mainsms.ru/api/mainsms',
        $useSSL     = false,
        $response   = null;

    /**
     * Конструктор
     *
     */
    public function __construct()
    {
        $this->project = config('mainsms.projectName');
        $this->key = config('mainsms.apiKey');
        $this->useSSL = config('mainsms.useSSL', false);
        $this->testMode = config('mainsms.testMode', false);
    }


    /**
     * Склейка параметров для формирования сигнатуры
     *
     * @param string $function
     * @param array $params
     *
     * @return array
     */

    protected function joinArrayBatchValues($params)
    {
        $result = array();
        foreach ($params as $name => $value) {
            $result[$name] = is_array($value) ? join(',', ( is_array(array_values($value)[0]) ?  $this->joinArrayBatchValues($value) : $value)) : $value;
        }

        return $result;
    }


    /**
     * Отправить запрос с пакетом SMS
     *
     * @param string $function
     * @param array $params
     *
     * @return stdClass
     */

    protected function makeBatchRequest($function, array $params = array())
    {
        $params_for_sign = $this->joinArrayBatchValues($params);
        $sign = $this->generateSign($params_for_sign);
        $params = array_merge(array('project' => $this->project), $params);

        $url = ($this->useSSL ? 'https://' : 'http://') . $this->url .'/'. $function;
        $post = http_build_query(array_merge($params, array('sign' => $sign)), '', '&');

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($this->useSSL) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            }
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => $post,
                    'timeout' => 10,
                ),
            ));
            $response = file_get_contents($url, false, $context);
        }

        return $this->response = json_decode($response, true);
    }

    /**
     * Отправить SMS
     *
     * @param string|array $recipients
     * @param string $message
     * @param string $sender
     * @param string $run_at
     *
     * @return boolean|integer
     */
    public function messageSend($recipients, $message, $sender, $run_at = null)
    {
        $params = array(
            'recipients'    => $recipients,
            'message'       => $message
        );

        if ($sender != null) {
            $params['sender'] = $sender;
        }

        if ($run_at != null) {
            $params['run_at'] = $run_at;
        }

        if ($this->testMode) {
            $params['test'] = 1;
        }

        $response = $this->makeRequest('message/send', $params);

        return $response['status'] == self::REQUEST_SUCCESS;
    }

    /**
     * Отмена запланированного сообщения
     *
     * @param string|array $messagesId
     *
     * @return boolean|array
     */
    public function messageCancel($messagesId)
    {
        if (! is_array($messagesId)) {
            $messagesId = array($messagesId);
        }

        $response = $this->makeRequest('message/cancel', array(
            'messages_id' => join(',', $messagesId),
        ));

        return $response['status'] == self::REQUEST_SUCCESS ? $response['messages'] : false;
    }

    /**
     * Проверить статус доставки сообщений
     *
     * @param string|array $messagesId
     *
     * @return boolean|array
     */
    public function messageStatus($messagesId)
    {
        if (! is_array($messagesId)) {
            $messagesId = array($messagesId);
        }

        $response = $this->makeRequest('message/status', array(
            'messages_id' => join(',', $messagesId),
        ));

        return $response['status'] == self::REQUEST_SUCCESS ? $response['messages'] : false;
    }

    /**
     * Запрос стоимости сообщения
     *
     * @param string|array $recipients
     * @param string $message
     *
     * @return boolean|decimal
     */
    public function messagePrice($recipients, $message)
    {
        $response = $this->makeRequest('message/price', array(
            'recipients'    => $recipients,
            'message'       => $message,
        ));

        return $response['status'] == self::REQUEST_SUCCESS ? $response['price'] : false;
    }

    /**
     * Запрос информации о номерах
     *
     * @param string|array $recipients
     *
     * @return boolean|decimal
     */
    public function phoneInfo($phones)
    {
        $response = $this->makeRequest('message/info', array(
            'phones'    => $phones
        ));

        return $response['status'] == self::REQUEST_SUCCESS ? $response['info'] : false;
    }


    /**
     * Запросить баланс
     *
     */
    public function userBalance()
    {
        $response = $this->makeRequest('message/balance');
        return $response['status'] == self::REQUEST_SUCCESS ? $response['balance'] : false;
    }


    /**
     * Отправить запрос
     *
     * @param string $function
     * @param array $params
     *
     * @return stdClass
     */
    protected function makeRequest($function, array $params = array())
    {
        $params = $this->joinArrayValues($params);
        $sign = $this->generateSign($params);
        $params = array_merge(array('project' => $this->project), $params);

        $url = ($this->useSSL ? 'https://' : 'http://') . $this->url .'/'. $function;
        $post = http_build_query(array_merge($params, array('sign' => $sign)), '', '&');

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($this->useSSL) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            }
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => $post,
                    'timeout' => 10,
                ),
            ));
            $response = file_get_contents($url, false, $context);
        }
        return $this->response = json_decode($response, true);
    }

    protected function joinArrayValues($params)
    {
        $result = array();
        foreach ($params as $name => $value) {
            $result[$name] = is_array($value) ? join(',', ( is_array(array_values($value)[0]) ?  $this->joinArrayValues($value) : $value)) : $value;
        }
        return $result;
    }


    /**
     * Установить адрес шлюза
     *
     * @param string $url
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }


    /**
     * Получить адрес сервера
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Добавить группу
     *
     * @param $name
     * @return boolean|integer
     */
    public function groupAdd($name)
    {
        $params = array(
            'name'    => $name
        );

        if ($this->testMode) {
            $params['test'] = 1;
        }

        $response = $this->makeRequest('group/create', $params);

        return $response['status'] == self::REQUEST_SUCCESS;
    }



    /**
     * Удалить группу
     *
     * @param integer $group
     *
     * @return boolean|integer
     */
    public function groupRemove($id)
    {
        $params = array( 'id' => $id );

        if ($this->testMode) {
            $params['test'] = 1;
        }

        $response = $this->makeRequest('group/remove', $params);

        return $response['status'] == self::REQUEST_SUCCESS;
    }



    /**
     * Получить список групп
     *
     * @param string|array $phone
     *
     * @return boolean|integer
     */
    public function groupGetAll($type = null)
    {
        $params = array();

        if ($type != null) {
            $params['type'] = $type;
        }

        if ($this->testMode) {
            $params['test'] = 1;
        }

        $response = $this->makeRequest('group/list', $params);

        return $response['status'] == self::REQUEST_SUCCESS;
    }

    /**
     * Добавить контакт
     *
     * @param string|array $phone
     * @param string $group
     * @param string $lastname
     * @param string $firstname
     * @param string $patronimic
     * @param string $birthday
     * @param string $param1
     * @param string $param2
     *
     * @return boolean|integer
     */
    public function contactAdd($phone, $group, $lastname = null, $firstname = null, $patronimic = null, $birthday = null, $param1 = null, $param2 = null)
    {
        $params = array(
            'phone'    => $phone,
            'group'    => $group
        );

        if ($lastname != null) {
            $params['lastname'] = $lastname;
        }

        if ($firstname != null) {
            $params['firstname'] = $firstname;
        }

        if ($patronimic != null) {
            $params['patronimic'] = $patronimic;
        }

        if ($param1 != null) {
            $params['param1'] = $param1;
        }

        if ($param2 != null) {
            $params['param2'] = $param2;
        }

        if ($this->testMode) {
            $params['test'] = 1;
        }

        $response = $this->makeRequest('contact/create', $params);

        return $response['status'] == self::REQUEST_SUCCESS;
    }


    /**
     * Удалить контакт
     *
     * @param string|array $phone
     * @param string $group
     *
     * @return boolean|integer
     */
    public function contactRemove($phone, $group = null)
    {
        $params = array( 'phone' => $phone );

        if ($group != null) {
            $params['group'] = $group;
        }

        if ($this->testMode) {
            $params['test'] = 1;
        }

        $response = $this->makeRequest('contact/remove', $params);

        return $response['status'] == self::REQUEST_SUCCESS;
    }



    /**
     * Получить контакт
     *
     * @param string|array $phone
     *
     * @return boolean|integer
     */
    public function contactGet($phone)
    {
        $params = array( 'phone' => $phone );

        if (isset($group) && $group != null) {
            $params['group'] = $group;
        }

        if ($this->testMode) {
            $params['test'] = 1;
        }

        $response = $this->makeRequest('contact/exists', $params);

        return $response['status'] == self::REQUEST_SUCCESS;
    }

    /**
     * Возвращает ответ сервера последнего запроса
     *
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }


    /**
     * Сгенерировать подпись
     *
     * @param array $params
     * @return string
     */
    protected function generateSign(array $params)
    {
        $params['project'] = $this->project;
        ksort($params);
        return md5(sha1(join(';', array_merge($params, Array($this->key)))));
    }
}