<?php

/**
 * This file is part of the Xi SMS package.
 *
 * For copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xi\Sms\Gateway;

use Xi\Sms\SmsMessage;
use Xi\Sms\SmsException;

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
	 * @param SmsMessage $message
     */
    public function send(SmsMessage $message)
    {
		// Sending is limited to max 100 addressees
		if (count($message->getTo()) > 100) {
			$return = array();
			foreach (array_chunk($message->getTo(), 100) as $tos) {
				$message_alt = clone $message;
				$message_alt->setTo($tos);
				$response = $this->send($message_alt);
				$return = array_merge($return, $response);
			}
			return $return;
		}

		$params = array(
			'api_id' => $this->apiKey,
			'user' => $this->user,
			'password' => $this->password,
			'to' => implode(',', $message->getTo()),
			'text' => utf8_decode($message->getBody()),
			'from' => $message->getFrom()
		);

		$response_string = $this->getClient()->get(
			$this->endpoint . '/http/sendmsg?'.http_build_query($params),
			array()
		);
		$response = $this->parseResponse($response_string);
		if (!empty($response['ERR'])) {
			throw new SmsException(sprintf('Error(s): %s', var_export($response['ERR'], true)));
		}
		if (empty($response['ID'])) {
			throw new SmsException('Error: No message ID returned');
		}
		return $response['ID'];
    }

	/**
	 * Parses a Clickatell HTTP API response
	 * @param string $response
	 * @return array error messages, messages IDs, phone numbers...
	 * @throws SmsException
	 */
	public static function parseResponse($response) {
		$return = array(
			'id' => null,
			'error' => null
		);
		if (preg_match_all('/((ERR|ID): ([^\n]*))+/', $response, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$phone_number = null;
				if (preg_match('/(.*)( To: ([0-9]+))$/', $matches[3][$i], $ms)) {
					$message = $ms[1];
					$phone_number = $ms[3];
				} else {
					$message = $matches[3][$i];
				}

				$key = $matches[2][$i];
				if ($phone_number) {
					$return[$key][$phone_number] = $message;
				} else {
					$return[$key] = $message;
				}
			}
			return $return;
		} else {
			throw new SmsException(sprintf('Could not parse response: %s', $response));
		}
	}
}
