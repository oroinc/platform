<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\EventListener;

use Oro\Bundle\IntegrationBundle\Event\ClientCreatedAfterEvent;
use Oro\Bundle\IntegrationBundle\EventListener\MultiAttemptsClientDecoratorListener;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator\MultiAttemptsClientDecorator;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\RestTransportSettingsInterface;
use Psr\Log\LoggerInterface;

class MultiAttemptsClientDecoratorListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var MultiAttemptsClientDecoratorListener */
    private $listener;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->listener = new MultiAttemptsClientDecoratorListener();
        $this->listener->setLogger($this->logger);
    }

    public function testDecoratorAttached()
    {
        $client = $this->createMock(RestClientInterface::class);
        $transport = $this->createMock(RestTransportSettingsInterface::class);
        $transport->expects($this->any())
            ->method('getOptions')
            ->willReturn([]);

        $event = new ClientCreatedAfterEvent($client, $transport);
        $this->listener->onClientCreated($event);

        $this->assertInstanceOf(
            MultiAttemptsClientDecorator::class,
            $event->getClient(),
            'decorator must be attached to client'
        );
    }

    public function testDecoratorNotAttached()
    {
        $configuration = MultiAttemptsClientDecoratorListener::getMultiAttemptsDisabledConfig();

        $client = $this->createMock(RestClientInterface::class);
        $transport = $this->createMock(RestTransportSettingsInterface::class);
        $transport->expects($this->any())
            ->method('getOptions')
            ->willReturn($configuration);

        $event = new ClientCreatedAfterEvent($client, $transport);
        $this->listener->onClientCreated($event);

        $this->assertSame($client, $event->getClient());
    }
}
