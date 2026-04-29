<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\WebhookRequestProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor\WebhookRequestContext;
use Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor\WebhookRequestProcessor;
use Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor\WebhookRequestProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class WebhookRequestProcessorTest extends TestCase
{
    private ContainerInterface&MockObject $serviceLocator;
    private WebhookRequestProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->serviceLocator = $this->createMock(ContainerInterface::class);
        $this->processor = new WebhookRequestProcessor($this->serviceLocator);
    }

    public function testProcessDelegatesWhenFormatExistsInServiceLocator(): void
    {
        $format = 'json_api';
        $context = new WebhookRequestContext(['topic' => 'order.created'], 'POST', [], [], []);
        $messageId = 'msg-001';

        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('getFormat')
            ->willReturn($format);

        $innerProcessor = $this->createMock(WebhookRequestProcessorInterface::class);
        $innerProcessor->expects(self::once())
            ->method('process')
            ->with($context, $webhook, $messageId, false);

        $this->serviceLocator->expects(self::once())
            ->method('has')
            ->with($format)
            ->willReturn(true);
        $this->serviceLocator->expects(self::once())
            ->method('get')
            ->with($format)
            ->willReturn($innerProcessor);

        $this->processor->process($context, $webhook, $messageId, false);
    }

    public function testProcessDoesNothingWhenFormatNotInServiceLocator(): void
    {
        $format = 'unknown_format';
        $context = new WebhookRequestContext(['topic' => 'order.created'], 'POST', [], [], []);
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('getFormat')
            ->willReturn($format);

        $this->serviceLocator->expects(self::once())
            ->method('has')
            ->with($format)
            ->willReturn(false);
        $this->serviceLocator->expects(self::never())
            ->method('get');

        // Must not throw
        $this->processor->process($context, $webhook, 'msg-002');
    }
}
