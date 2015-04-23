<?php

/**
 * This file is part of the Xi SMS package.
 *
 * For copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xi\Sms\Gateway;

use Xi\Sms\SmsMessage;

class ClickatellGateway extends BaseHttpRequestGateway
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $endpoint;

    public function __construct(
        $apiKey,
        $user,
        $password,
        $endpoint = 'https://api.clickatell.com'
    ) {
        $this->apiKey = $apiKey;
        $this->user = $user;
        $this->password = $password;
        $this->endpoint = $endpoint;
    }

    /**
     * @see GatewayInterface::send
     * @todo Implement a smarter method of sending (batch)
	 * @param SmsMessage $message
	 * @param bool $utf8decode To ensure backwards compatibility
     */
    public function send(SmsMessage $message, $utf8decode = true)
    {
        foreach ($message->getTo() as $to) {
			$params = array(
				'api_id' => $this->apiKey,
				'user' => $this->user,
				'password' => $this->password,
				'to' => $to,
				'text' => $message->getBody(),
				'from' => $message->getFrom()
			);

			// BC
			if ($utf8decode) {
				$params['text'] = utf8_decode($params['text']);
			}

			$this->getClient()->get(
				$this->endpoint . '/http/sendmsg?'.http_build_query($params),
				array()
			);
        }
        return true;
    }
}
