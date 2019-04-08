<?php

/**
 * This file is part of the Xi SMS package.
 *
 * For copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This Gateway implement Twilio API
 * https://www.twilio.com/docs/libraries/php
 */

namespace Xi\Sms\Gateway;

use Xi\Sms\SmsMessage;
use Xi\Sms\SmsException;

class TwilioGateway extends BaseHttpRequestGateway
{
    /**
     * @var string
     */
    private $accountSid;

    /**
     * @var string
     */
    private $authToken;

	/**
	 * @var string
	 */
	private $numberFrom;

	/**
	 * @var string|null
	 */
	private $messagingServiceId;

	/**
	 * TwilioGateway constructor.
	 * @param string $accountSid Your Account SID from twilio.com/console
	 * @param string $authToken Your Auth Token from twilio.com/console
	 * @param string $numberFrom A Twilio phone number you purchased at twilio.com/console
	 * @param string|null $messagingServiceId
	 */
    public function __construct(
        $accountSid,
        $authToken,
        $numberFrom,
		$messagingServiceId = null
    ) {
        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
		$this->numberFrom = $numberFrom;
		$this->messagingServiceId = $messagingServiceId;
    }

    /**
     * @see GatewayInterface::send
     * @param SmsMessage $message
     * @return string[]|null[]
     * @throws SmsException
     */
    public function send(SmsMessage $message)
    {
        $return = array();
        foreach ($message->getTo() as $to) {
            $message_id = $this->sendMessage($message->getFrom(), $to, $message->getBody());
            $return[] = $message_id;
        }
        if (count($message->getTo()) === 1) {
            return reset($return);
        }
        return $return;
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $content
     * @return string|null
     * @throws SmsException
     */
    protected function sendMessage($from, $to, $content) 
    {
        try {
            $client = $this->getTwilioClient();
        } catch (\Twilio\Exceptions\ConfigurationException $e) {
            throw new SmsException('Invalid Twilio configuration');
        }

        $params = array(
			'from' => '+' . $this->numberFrom,
			// the body of the text message you'd like to send
			'body' => $content,
		);
        if ($this->messagingServiceId) {
			$params['messagingServiceId'] = $this->messagingServiceId;
		}

        // Use the client to do fun stuff like send text messages!
        $MessageInstance = $client->messages->create(
        // the number you'd like to send the message to
            '+' . $to,
			$params
        );
        $MessageInstance = $MessageInstance->toArray();
        if (empty($MessageInstance['sid'])) {
            return null;
        }
        return $MessageInstance['sid'];
    }

    /**
     * @return \Buzz\Browser|\Twilio\Rest\Client
     * @throws \Twilio\Exceptions\ConfigurationException
     */
    protected function getTwilioClient() 
    {
        return new \Twilio\Rest\Client($this->accountSid, $this->authToken);
    }
}
