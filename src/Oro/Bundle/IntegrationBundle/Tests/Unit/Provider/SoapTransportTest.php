<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Provider\SOAPTransport;

class SoapTransportTest extends \PHPUnit_Framework_TestCase
{
    /** @var SOAPTransport */
    protected $transport;

    /**
     * Setup test entity
     */
    public function setUp()
    {
        $this->transport = $this->getMockForAbstractClass(
            'Oro\Bundle\IntegrationBundle\Provider\SOAPTransport',
            [],
            '',
            true,
            true,
            true,
            ['getSoapClient']
        );

        $this->soapClientMock = $this->getMockBuilder('\SoapClient')
            ->disableOriginalConstructor()
            ->setMethods(['__soapCall'])
            ->getMock();

        $this->settings = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');
    }

    /**
     * Tear down setup objects
     */
    public function tearDown()
    {
        unset($this->transport, $this->soapClientMock, $this->settings);
    }

    /**
     * Test init method
     */
    public function testInit()
    {
        $isDebug = false;

        $this->transport->expects($this->once())
            ->method('getSoapClient')
            ->will($this->returnValue($this->soapClientMock));

        $this->settings->expects($this->at(0))
            ->method('get')
            ->with('wsdl_url')
            ->will($this->returnValue('http://localhost.not.exists/?wsdl'));
        $this->settings->expects($this->at(1))
            ->method('get')
            ->with('debug')
            ->will($this->returnValue($isDebug));

        try {
            $result = $this->transport->init($this->settings);
            $this->assertTrue($result);
        } catch (\SoapFault $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->transport->call('test');

        $this->assertEmpty($this->transport->getLastRequest());
        $this->assertEmpty($this->transport->getLastResponse());
    }

    /**
     * Test init method errors
     *
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInitErrors()
    {
        $this->settings->expects($this->at(0))
            ->method('get')
            ->with('wsdl_url')
            ->will($this->returnValue(null));

        $this->transport->init($this->settings);
    }
}
