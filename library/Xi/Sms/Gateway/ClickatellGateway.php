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
		if (count($message->getTo()) > 100) {
			// @todo chunk $message->getTo() by 100
			throw new SmsException('Error: sending through to 100+ addresses is not yet implemented');
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
		if (!empty($response['error'])) {
			throw new SmsException(sprintf('Error(s): %s', var_export($response['error'], true)));
		}
		if (empty($response['id'])) {
			throw new SmsException('Error: No message ID returned');
		}
		return $response['id'];
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

				if ($matches[2][$i] === 'ERR') {
					if ($phone_number) {
						$return['error'][$phone_number] = $message;
					} else {
						$return['error'] = $message;
					}
				} elseif ($matches[2][$i] === 'ID') {
					if ($phone_number) {
						$return['id'][$phone_number] = $message;
					} else {
						$return['id'] = $message;
					}
				}
			}
			return $return;
		} else {
			throw new SmsException(sprintf('Could not parse response: %s', $response));
		}
	}
}
