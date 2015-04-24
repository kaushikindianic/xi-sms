<?php

namespace Xi\Sms\Tests\Gateway;

use Xi\Sms\SmsMessage;
use Xi\Sms\SmsService;
use Xi\Sms\SmsException;
use Xi\Sms\Gateway\ClickatellGateway;
use Buzz\Message\Response;

class ClickatellGatewayTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @test
	 */
	public function parseResponse5()
	{
		$response = ClickatellGateway::parseResponse("ERR: 114, Cannot route message To: 49123456789\nERR: 567, Bla bla bla To: 4987654321");
		$this->assertEquals('114, Cannot route message', $response['error']['49123456789']);
		$this->assertEquals('567, Bla bla bla', $response['error']['4987654321']);
	}

	/**
	 * @test
	 */
	public function parseResponse4()
	{
		$response = ClickatellGateway::parseResponse("ID: CE07B3BFEFF35F4E2667B3A47116FDD2 To: 49123456789\nID: QWERTYUIO123456789ASDFGHJK To: 4987654321");
		$this->assertEquals('CE07B3BFEFF35F4E2667B3A47116FDD2', $response['id']['49123456789']);
		$this->assertEquals('QWERTYUIO123456789ASDFGHJK', $response['id']['4987654321']);
	}

	/**
	 * @test
	 */
	public function parseResponse3()
	{
		$this->setExpectedException('Xi\Sms\SmsException');
		$response = ClickatellGateway::parseResponse('foo bar');
	}

	/**
	 * @test
	 */
	public function parseResponse2()
	{
		$response = ClickatellGateway::parseResponse('ID: CE07B3BFEFF35F4E2667B3A47116FDD2');
		$this->assertEquals('CE07B3BFEFF35F4E2667B3A47116FDD2', $response['id']);
	}

	/**
	 * @test
	 */
	public function parseResponse1()
	{
		$response = ClickatellGateway::parseResponse('ERR: 114, Cannot route message');
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
