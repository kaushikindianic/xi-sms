<?php

namespace Xi\Sms\Tests\Gateway;

class TwilioGatewayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $accountSid = 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';

    /**
     * @var string
     */
    protected $authToken = 'your_auth_token';

    /**
     * @var string
     */
    protected $numberFrom = '15017250604';

    /**
     * @var string
     */
    protected $numberTo = '15558675309';

    /**
     * @test
     */
    public function test_send()
    {
        $Client = new \Twilio\Rest\Client(
            $this->accountSid,
            $this->authToken
        );
        $Client->messages = $this->getMock(
            '\Twilio\Rest\Api\V2010\Account\MessageList',
            array('create'),
            array(
                $this->mockVersion(),
                $this->accountSid
            )
        );
        $Client->messages
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->mockMessageInterface()));

        $gateway = $this->getMock(
            '\Xi\Sms\Gateway\TwilioGateway',
            array('getTwilioClient'),
            array(
                $this->accountSid,
                $this->authToken,
                $this->numberFrom
            )
        );
        $gateway
            ->expects($this->once())
            ->method('getTwilioClient')
            ->will($this->returnValue($Client));

        $message = new \Xi\Sms\SmsMessage(
            'Hey Jenny! Good luck on the bar exam!',
            null,
            $this->numberTo
        );

        $ret = $gateway->send($message);

        $this->assertSame('SMXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', $ret);
    }

    /**
     * @return \Twilio\Rest\Api\V2010\Account\MessageInstance
     * @throws \Twilio\Exceptions\ConfigurationException
     */
    protected function mockMessageInterface() 
    {
        $payload = array (
            'sid' => 'SMXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'date_created' => 'Tue, 22 Jan 2019 04:25:24 +0000',
            'date_updated' => 'Tue, 22 Jan 2019 04:25:24 +0000',
            'date_sent' => NULL,
            'account_sid' => $this->accountSid,
            'to' => '+' . $this->numberTo,
            'from' => '+' . $this->numberFrom,
            'messaging_service_sid' => NULL,
            'body' => 'Hey Jenny! Good luck on the bar exam!',
            'status' => 'queued',
            'num_segments' => '1',
            'num_media' => '0',
            'direction' => 'outbound-api',
            'api_version' => '2010-04-01',
            'price' => NULL,
            'price_unit' => 'USD',
            'error_code' => NULL,
            'error_message' => NULL,
            'uri' => '/2010-04-01/Accounts/' . $this->accountSid . '/Messages/SMXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX.json',
            'subresource_uris' =>
                array (
                    'media' => '/2010-04-01/Accounts/' . $this->accountSid . '/Messages/SMXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX/Media.json',
                ),
        );

        $MessageInstance = new \Twilio\Rest\Api\V2010\Account\MessageInstance(
            $this->mockVersion(),
            $payload,
            $this->accountSid
        );

        return $MessageInstance;
    }

    /**
     * @return \Twilio\Rest\Api\V2010
     * @throws \Twilio\Exceptions\ConfigurationException
     */
    protected function mockVersion() 
    {
        $Client = new \Twilio\Rest\Client(
            $this->accountSid,
            $this->authToken
        );
        $Api = new \Twilio\Rest\Api($Client);
        $Version = new \Twilio\Rest\Api\V2010($Api);
        return $Version;
    }
}
