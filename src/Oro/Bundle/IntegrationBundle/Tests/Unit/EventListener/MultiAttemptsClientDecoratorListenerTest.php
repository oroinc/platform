<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\EventListener;

use Oro\Bundle\IntegrationBundle\Event\ClientCreatedAfterEvent;
use Oro\Bundle\IntegrationBundle\EventListener\MultiAttemptsClientDecoratorListener;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator\MultiAttemptsClientDecorator;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\RestTransportSettingsInterface;
use Oro\Bundle\IntegrationBundle\Utils\MultiAttemptsConfigTrait;
use Psr\Log\LoggerInterface;

class MultiAttemptsClientDecoratorListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MultiAttemptsClientDecoratorListener
     */
    protected $listener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject | LoggerInterface
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new MultiAttemptsClientDecoratorListener();
        $this->listener->setLogger($this->logger);
    }

    public function testDecoratorAttached()
    {
        $client = $this->createMock(RestClientInterface::class);
        $transport = $this->createMock(RestTransportSettingsInterface::class);
        $transport
            ->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue([]));

        $event = new ClientCreatedAfterEvent($client, $transport);
        $this->listener->onClientCreated($event);

        $this->assertInstanceOf(
            MultiAttemptsClientDecorator::class,
            $event->getClient(),
            "Decorator must be attached to client !"
        );
    }

    public function testDecoratorNotAttached()
    {
        $configuration = MultiAttemptsConfigTrait::getMultiAttemptsDisabledConfig();

        $client = $this->createMock(RestClientInterface::class);
        $transport = $this->createMock(RestTransportSettingsInterface::class);
        $transport
            ->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue($configuration));

        $event = new ClientCreatedAfterEvent($client, $transport);
        $this->listener->onClientCreated($event);

        $this->assertSame($client, $event->getClient());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->logger, $this->listener);
    }
}
