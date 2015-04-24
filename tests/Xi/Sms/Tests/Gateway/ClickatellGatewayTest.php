<?php

namespace Xi\Sms\Tests\Gateway;

use Xi\Sms\Gateway\ClickatellGateway;

class ClickatellGatewayTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @test
	 */
	public function parseResponse1()
	{
		$response = ClickatellGateway::parseResponse('ERR: 114, Cannot route message');
		$this->assertArrayHasKey('id', $response);
		$this->assertArrayHasKey('error', $response);
		$this->assertEquals('114, Cannot route message', $response['error']);
	}

    /**
     * @test
     */
    public function sendsRequest()
    {
        $gateway = new ClickatellGateway('lussavain', 'lussuta', 'tussia', 'http://api.dr-kobros.com');

        $browser = $this->getMockBuilder('Buzz\Browser')
            ->disableOriginalConstructor()
            ->getMock();

        $gateway->setClient($browser);

        $browser
            ->expects($this->once())
            ->method('get')
            ->with(
				$this->callback(function($actual) {
					$url = parse_url($actual);
					parse_str($url['query'], $query);
					return
						$url['scheme'] === 'http' &&
						$url['host'] === 'api.dr-kobros.com' &&
						$url['path'] === '/http/sendmsg' &&
						$query['api_id'] === 'lussavain' &&
						$query['user'] === 'lussuta' &&
						$query['password'] === 'tussia' &&
						$query['to'] === '358503028030' &&
						urldecode($query['text']) === 'Pekkis tassa lussuttaa.' &&
						$query['from'] === '358503028030';
				}),
                array()
            );

        $message = new \Xi\Sms\SmsMessage(
            'Pekkis tassa lussuttaa.',
            '358503028030',
            '358503028030'
        );

        $ret = $gateway->send($message);
        $this->assertTrue($ret);
    }
}
