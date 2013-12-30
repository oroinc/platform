<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Symfony\Component\HttpKernel\Log\NullLogger;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
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
        $loggerStrategy  = new LoggerStrategy(new NullLogger());
        $this->connector = $this->getMockForAbstractClass(
            'Oro\Bundle\IntegrationBundle\Provider\AbstractConnector',
            [new ContextRegistry(), $loggerStrategy]
        );

        $this->transport     = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Transport');
        $this->realTransport = $this->getMock('Oro\Bundle\IntegrationBundle\Provider\TransportInterface');
    }

    /**
     * Tear down setup objects
     */
    public function tearDown()
    {
        unset($this->connector, $this->transport, $this->realTransport);
    }

    public function testGetLabel()
    {
        $this->assertNull($this->connector->getLabel());
    }
}
