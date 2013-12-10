<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;

class AbstractConnectorTest extends \PHPUnit_Framework_TestCase
{
    /** @var AbstractConnector */
    protected $connector;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $transport;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $realTransport;

    /**
     * Setup test entity
     */
    public function setUp()
    {
        $this->connector = $this->getMockForAbstractClass('Oro\Bundle\IntegrationBundle\Provider\AbstractConnector');

        $this->transport = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Transport');
        $this->realTransport = $this->getMock('Oro\Bundle\IntegrationBundle\Provider\TransportInterface');
    }

    /**
     * Tear down setup objects
     */
    public function tearDown()
    {
        unset($this->connector, $this->transport, $this->realTransport);
    }

    /**
     * Test connect method
     */
    public function testConnect()
    {
        $this->connector->configure($this->realTransport, $this->transport);
        $params = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');

        $this->transport->expects($this->once())
            ->method('getSettingsBag')
            ->will($this->returnValue($params));

        $this->realTransport->expects($this->once())
            ->method('init')
            ->with($params)
            ->will($this->returnValue(true));

        $this->realTransport->expects($this->once())
            ->method('call');

        $obj = new \ReflectionObject($this->connector);
        $method = $obj->getMethod('call');
        $method->setAccessible(true);
        $method->invoke($this->connector, 'test');
    }

    /**
     * Test init method errors
     *
     * @expectedException \LogicException
     */
    public function testConnectErrors()
    {
        $this->connector->connect();
    }

    /**
     * Test protected method call
     */
    public function estCall()
    {
        $obj = new \ReflectionObject($this->connector);
        $method = $obj->getMethod('call');
        $method->setAccessible(true);
        $method->invoke($this->connector, 'test');
    }
}
