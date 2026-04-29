<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Processor;

use Oro\Bundle\IntegrationBundle\Exception\UnknownWebhookProcessorException;
use Oro\Bundle\IntegrationBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Processor\WebhookProcessorInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessorRegistryTest extends TestCase
{
    private WebhookProcessorInterface&MockObject $processor;
    private ProcessorRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->processor = $this->createMock(WebhookProcessorInterface::class);
        $locator = TestContainerBuilder::create()
            ->add('test_processor', $this->processor)
            ->getContainer($this);

        $this->registry = new ProcessorRegistry($locator);
    }

    public function testGetProcessorReturnsRegisteredProcessor(): void
    {
        $result = $this->registry->getProcessor('test_processor');

        self::assertSame($this->processor, $result);
    }

    public function testGetProcessorThrowsExceptionForUnknownProcessor(): void
    {
        $this->expectException(UnknownWebhookProcessorException::class);
        $this->expectExceptionMessage('Processor "unknown_processor" is not registered.');

        $this->registry->getProcessor('unknown_processor');
    }
}
