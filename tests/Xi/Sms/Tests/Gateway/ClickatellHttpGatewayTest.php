<?php

namespace Xi\Sms\Tests\Gateway;

class ClickatellHttpGatewayTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @test
	 */
	public function test_send_multiple()
	{
		$gateway = new \Xi\Sms\Gateway\ClickatellHttpGateway('XXXXXXXXXX-X-XXXXXXXXX==');

		$browser = $this->getMockBuilder('Buzz\Browser')
			->disableOriginalConstructor()
			->getMock();

		$gateway->setClient($browser);

		$Response = new \Buzz\Message\Response();
		$Response->setContent('{"messages":[{"apiMessageId":"QWERTYUI12345678","accepted":true,"to":"358503028030","error":null}],"error":null}');

		$browser
			->expects($this->exactly(2))
			->method('get')
			->will($this->returnValue($Response));

		$message = new \Xi\Sms\SmsMessage(
			'Pekkis tassa lussuttaa.',
			'358503028030',
			array('358503028030', '49123456789')
		);
		$ret = $gateway->send($message);
	}

	/**
	 * @test
	 */
	public function test_send_twoWay_errorDescription()
	{
		$gateway = new \Xi\Sms\Gateway\ClickatellHttpGateway('XXXXXXXXXX-X-XXXXXXXXX==');

		$browser = $this->getMockBuilder('Buzz\Browser')
			->disableOriginalConstructor()
			->getMock();

		$gateway->setClient($browser);

		$Response = new \Buzz\Message\Response();
		$Response->setContent('{"messages":[],"errorCode":607,"error":"Invalid FROM number.","errorDescription":"User specified FROM number, but integration isnt two-way."}');

		$browser
			->expects($this->at(0))
			->method('get')
			->will($this->returnValue($Response));

		$Response = new \Buzz\Message\Response();
		$Response->setContent('{"messages":[{"apiMessageId":"QWERTYUI12345678","accepted":true,"to":"358503028030","error":null}],"error":null}');

		$browser
			->expects($this->at(1))
			->method('get')
			->will($this->returnValue($Response));

		$message = new \Xi\Sms\SmsMessage(
			'Pekkis tassa lussuttaa.',
			'358503028030',
			'358503028030'
		);

		$ret = $gateway->send($message);
		$this->assertEquals('QWERTYUI12345678', $ret);
	}

	/**
	 * @test
	 */
	public function test_send_twoWay()
	{
		$gateway = new \Xi\Sms\Gateway\ClickatellHttpGateway('XXXXXXXXXX-X-XXXXXXXXX==');

		$browser = $this->getMockBuilder('Buzz\Browser')
			->disableOriginalConstructor()
			->getMock();

		$gateway->setClient($browser);

		$Response = new \Buzz\Message\Response();
		$Response->setContent('{"messages":[],"errorCode":607,"error":"User specified FROM number, but integration isnt two-way.","errorDescription":""}');

		$browser
			->expects($this->at(0))
			->method('get')
			->will($this->returnValue($Response));

		$Response = new \Buzz\Message\Response();
		$Response->setContent('{"messages":[{"apiMessageId":"QWERTYUI12345678","accepted":true,"to":"358503028030","error":null}],"error":null}');

		$browser
			->expects($this->at(1))
			->method('get')
			->will($this->returnValue($Response));

		$message = new \Xi\Sms\SmsMessage(
			'Pekkis tassa lussuttaa.',
			'358503028030',
			'358503028030'
		);

		$ret = $gateway->send($message);
		$this->assertEquals('QWERTYUI12345678', $ret);
	}

    /**
     * @test
     */
    public function test_send()
    {
        $gateway = new \Xi\Sms\Gateway\ClickatellHttpGateway('XXXXXXXXXX-X-XXXXXXXXX==');

        $browser = $this->getMockBuilder('Buzz\Browser')
            ->disableOriginalConstructor()
            ->getMock();

        $gateway->setClient($browser);

		$Response = new \Buzz\Message\Response();
		$Response->setContent('{"messages":[{"apiMessageId":"QWERTYUI12345678","accepted":true,"to":"358503028030","error":null}],"error":null}');

        $browser
            ->expects($this->once())
            ->method('get')
            ->with(
				$this->callback(function($actual) {
					$url = parse_url($actual);
					parse_str($url['query'], $query);
					return
						$url['host'] === 'platform.clickatell.com' &&
						$url['path'] === '/messages/http/send' &&
						$query['apiKey'] === 'XXXXXXXXXX-X-XXXXXXXXX==' &&
						$query['to'] === '358503028030' &&
						urldecode($query['content']) === 'Pekkis tassa lussuttaa.' &&
						$query['from'] === '358503028030';
				})
            )
			->will($this->returnValue($Response));

        $message = new \Xi\Sms\SmsMessage(
            'Pekkis tassa lussuttaa.',
            '358503028030',
            '358503028030'
        );

        $ret = $gateway->send($message);
        $this->assertEquals('QWERTYUI12345678', $ret);
    }
}
