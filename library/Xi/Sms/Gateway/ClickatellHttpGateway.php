<?php

/**
 * This file is part of the Xi SMS package.
 *
 * For copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This Gateway implement Clickatell HTTP API
 * https://www.clickatell.com/developers/api-documentation/http-api-send-message/
 */

namespace Xi\Sms\Gateway;

use Xi\Sms\SmsMessage;
use Xi\Sms\SmsException;

class ClickatellHttpGateway extends BaseHttpRequestGateway
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $endpoint;

    public function __construct(
        $apiKey,
        $endpoint = 'https://platform.clickatell.com'
    ) {
        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
    }

    /**
     * @see GatewayInterface::send
	 * @param SmsMessage $message
     */
    public function send(SmsMessage $message)
	{
		$return = [];
		foreach ($message->getTo() as $to) {
			$message_id = $this->_send($message->getFrom(), $to, $message->getBody());
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
	 * @return string
	 * @throws SmsException
	 */
	protected function _send($from, $to, $content) {

		$params = array(
			'apiKey' => $this->apiKey,
			'to' => $to,
			'content' => $content,
			/**
			 * Mobile originated (required for USA and Canada)
			 * http://stackoverflow.com/questions/36584831/clickatell-http-api-send-message-fails-with-routing-error-status-9
			 */
			'mo' => 1,
		);

		if ($from) {
			$params['from'] = $from;
		}

		$response = $this->getClient()->get(
			$this->endpoint . '/messages/http/send?'.http_build_query($params),
			[
				'Accept' => 'application/json',
			]
		);

		$body = json_decode($response->getContent(), true);

		// If Body is not JSON-encoded
		if (json_last_error() != JSON_ERROR_NONE) {
			throw new SmsException(sprintf('Could not parse API response: %s', var_export($response->getContent(), true)));
		}

		if (!empty($body['error']) || empty($body['messages'])) {
			throw new SmsException(sprintf('Error(s): %s', var_export($body, true)));
		}

		$message = reset($body['messages']);

		if (!empty($message['error']) || empty($message['apiMessageId'])) {
			throw new SmsException(sprintf('Error(s): %s', var_export($body, true)));
		}

		return $message['apiMessageId'];
    }
}
