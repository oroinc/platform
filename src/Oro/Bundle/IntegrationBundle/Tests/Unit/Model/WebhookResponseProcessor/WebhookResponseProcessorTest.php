<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\WebhookResponseProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor\WebhookResponseProcessor;
use Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor\WebhookResponseProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\ResponseInterface;

class WebhookResponseProcessorTest extends TestCase
{
    private ResponseInterface&MockObject $response;
    private WebhookProducerSettings&MockObject $webhook;

    #[\Override]
    protected function setUp(): void
    {
        $this->response = $this->createMock(ResponseInterface::class);
        $this->webhook = $this->createMock(WebhookProducerSettings::class);
    }

    public function testProcessDelegatesToFirstSupportingProcessor(): void
    {
        $unsupported = $this->createMock(WebhookResponseProcessorInterface::class);
        $unsupported->expects(self::once())
            ->method('supports')
            ->willReturn(false);
        $unsupported->expects(self::never())
            ->method('process');

        $supported = $this->createMock(WebhookResponseProcessorInterface::class);
        $supported->expects(self::once())
            ->method('supports')
            ->willReturn(true);
        $supported->expects(self::once())
            ->method('process')
            ->with($this->response, $this->webhook, 'msg-001', false)
            ->willReturn(true);

        $processor = new WebhookResponseProcessor([$unsupported, $supported]);
        $result = $processor->process($this->response, $this->webhook, 'msg-001');

        self::assertTrue($result);
    }

    public function testProcessReturnsTrueWhenNoProcessorSupports(): void
    {
        $p1 = $this->createMock(WebhookResponseProcessorInterface::class);
        $p1->expects(self::once())
            ->method('supports')
            ->willReturn(false);

        $p2 = $this->createMock(WebhookResponseProcessorInterface::class);
        $p2->expects(self::once())
            ->method('supports')
            ->willReturn(false);

        $processor = new WebhookResponseProcessor([$p1, $p2]);
        $result = $processor->process($this->response, $this->webhook, 'msg-002');

        self::assertTrue($result);
    }

    public function testProcessStopsAtFirstSupportingProcessor(): void
    {
        $first = $this->createMock(WebhookResponseProcessorInterface::class);
        $first->expects(self::once())
            ->method('supports')
            ->willReturn(true);
        $first->expects(self::once())
            ->method('process')
            ->willReturn(false);

        $second = $this->createMock(WebhookResponseProcessorInterface::class);
        $second->expects(self::never())
            ->method('supports');
        $second->expects(self::never())
            ->method('process');

        $processor = new WebhookResponseProcessor([$first, $second]);
        $result = $processor->process($this->response, $this->webhook, 'msg-003');

        self::assertFalse($result);
    }

    public function testProcessReturnsTrueWithEmptyProcessorList(): void
    {
        $processor = new WebhookResponseProcessor([]);
        $result = $processor->process($this->response, $this->webhook, 'msg-004');

        self::assertTrue($result);
    }

    public function testProcessPassesThrowExceptionOnErrorToDelegate(): void
    {
        $supported = $this->createMock(WebhookResponseProcessorInterface::class);
        $supported->expects(self::once())
            ->method('supports')
            ->willReturn(true);
        $supported->expects(self::once())
            ->method('process')
            ->with($this->response, $this->webhook, 'msg-005', true)
            ->willReturn(true);

        $processor = new WebhookResponseProcessor([$supported]);
        $processor->process($this->response, $this->webhook, 'msg-005', true);
    }
}
