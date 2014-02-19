<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\SimpleTransport;

class SimpleTransportTest extends \PHPUnit_Framework_TestCase
{
    /** @var SimpleTransport */
    protected $transport;

    /** @var Transport|\PHPUnit_Framework_MockObject_MockObject */
    protected $transportEntity;

    public function setUp()
    {
        $this->transport       = new SimpleTransport();
        $settings              = new ParameterBag();
        $this->transportEntity = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Transport');
        $this->transportEntity->expects($this->any())->method('getSettingsBag')
            ->will($this->returnValue($settings));
    }

    public function tearDown()
    {
        unset($this->transport, $this->transportEntity);
    }

    public function testTransport()
    {
        $this->assertInstanceOf('Oro\Bundle\IntegrationBundle\Provider\TransportInterface', $this->transport);

        $this->assertNull($this->transport->getSettingsEntityFQCN());
        $this->assertNotEmpty($this->transport->getLabel());
        $this->assertEquals('hidden', $this->transport->getSettingsFormType());
        $this->transport->init($this->transportEntity);
    }
}
