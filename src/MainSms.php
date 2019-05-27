<?php

namespace Grechanyuk\MainSms;

use Grechanyuk\MainSms\Services\MainSmsService;

class MainSms extends MainSmsService
{
    /**
     * Отправить SMS
     *
     * @param string|array $recipients
     * @param string $message
     * @param string|null $sender
     *
     * @param string|null $run_at
     * @return boolean|integer
     * @deprecated
     */
    public function sendSMS($recipients, $message, $sender = null, $run_at = null)
    {
        return $this->messageSend($recipients, $message, $sender, $run_at);
    }


    /**
     * Отправить Пакет SMS
     *
     * @param string $sender
     * @param array $messages [["id"=>$id, "phone"=>$phone, "text"=>$text], [...] , ... ]
     *
     * @return boolean|integer
     * @deprecated
     */

    public function sendBatch($sender, $messages)
    {
        $params = array(
            'messages'       => $messages,
            'sender'        => $sender,
        );

        if ($this->testMode) {
            $params['test'] = 1;
        }

        $response = $this->makeBatchRequest('batch/send', $params);

        return $response['status'] == self::REQUEST_SUCCESS;
    }

    /**
     * Отменить запланированое сообщение
     *
     * @param string|array $messagesId
     *
     * @return boolean|array
     * @deprecated
     */
    public function cancelSMS($messagesId)
    {
        return $this->messageCancel($messagesId);
    }

    /**
     * Проверить статус доставки сообщений
     *
     * @param string|array $messagesId
     *
     * @return boolean|array
     * @deprecated
     */
    public function checkStatus($messagesId)
    {
        return $this->messageStatus($messagesId);
    }

    /**
     * Запросить баланс
     *
     */
    public function getBalance()
    {
        return $this->userBalance();
    }
}