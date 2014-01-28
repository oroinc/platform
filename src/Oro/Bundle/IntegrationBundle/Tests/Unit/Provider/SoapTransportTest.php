<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\SOAPTransport;

class SoapTransportTest extends \PHPUnit_Framework_TestCase
{
    /** @var SOAPTransport|\PHPUnit_Framework_MockObject_MockObject */
    protected $transport;

    /** @var Transport|\PHPUnit_Framework_MockObject_MockObject */
    protected $transportEntity;

    /** @var ParameterBag */
    protected $settings;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $soapClientMock;

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

        $this->settings        = new ParameterBag();
        $this->transportEntity = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Transport');
        $this->transportEntity->expects($this->any())->method('getSettingsBag')
            ->will($this->returnValue($this->settings));
    }

    /**
     * Tear down setup objects
     */
    public function tearDown()
    {
        unset($this->transport, $this->transportEntity, $this->soapClientMock, $this->settings);
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

        $this->settings->set('wsdl_url', 'http://localhost.not.exists/?wsdl');
        $this->settings->set('debug', $isDebug);

        try {
            $this->transport->init($this->transportEntity);
        } catch (\SoapFault $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->transport->call('test');

        $this->assertEmpty($this->transport->getLastRequest());
        $this->assertEmpty($this->transport->getLastResponse());
    }

    /**
     * Test init method errors
     */
    public function testInitErrors()
    {
        $this->setExpectedException('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');
        $this->transport->init($this->transportEntity);
    }
}
