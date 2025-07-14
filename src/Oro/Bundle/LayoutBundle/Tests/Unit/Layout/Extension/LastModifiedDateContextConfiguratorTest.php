<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\LastModifiedDateContextConfigurator;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\LastModificationDateProvider;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LastModifiedDateContextConfiguratorTest extends TestCase
{
    private LastModificationDateProvider&MockObject $lastModificationDateProvider;
    private LastModifiedDateContextConfigurator $contextConfigurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->lastModificationDateProvider = $this->createMock(LastModificationDateProvider::class);

        $this->contextConfigurator = new LastModifiedDateContextConfigurator($this->lastModificationDateProvider);
    }

    public function testConfigureContextWithDefaultAction(): void
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertIsString($context['last_modification_date']);
    }

    public function testConfigureContext(): void
    {
        $lastModificationDate = new \DateTime('now - 1 hour', new \DateTimeZone('UTC'));
        $context = new LayoutContext();

        $this->lastModificationDateProvider->expects($this->once())
            ->method('getLastModificationDate')
            ->willReturn($lastModificationDate);

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals(
            $lastModificationDate->format(\DateTime::COOKIE),
            $context['last_modification_date']
        );
    }

    public function testConfigureContextWhenLastModificationDateDoesNotExist(): void
    {
        $context = new LayoutContext();

        $this->lastModificationDateProvider->expects($this->once())
            ->method('getLastModificationDate')
            ->willReturn(null);

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertNotEmpty($context['last_modification_date']);
    }
}
