<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Provider\SimpleTransport;
use Symfony\Component\HttpFoundation\ParameterBag;

class SimpleTransportTest extends \PHPUnit_Framework_TestCase
{
    /** @var SimpleTransport */
    protected $transport;

    public function setUp()
    {
        $this->transport = new SimpleTransport();
    }

    public function tearDown()
    {
        unset($this->transport);
    }

    /**
     * Test
     */
    public function testTransport()
    {
        $this->assertInstanceOf('Oro\Bundle\IntegrationBundle\Provider\TransportInterface', $this->transport);

        $this->assertNull($this->transport->getSettingsEntityFQCN());
        $this->assertNotEmpty($this->transport->getLabel());
        $this->assertTrue($this->transport->init(new ParameterBag()));
        $this->assertEmpty($this->transport->call('test'));
        $this->assertEquals('hidden', $this->transport->getSettingsFormType());
    }
}
