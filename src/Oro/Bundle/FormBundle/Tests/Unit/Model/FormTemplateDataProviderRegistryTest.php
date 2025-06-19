<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class FormTemplateDataProviderRegistryTest extends TestCase
{
    private FormTemplateDataProviderInterface&MockObject $provider1;
    private FormTemplateDataProviderRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(FormTemplateDataProviderInterface::class);
        $providers = new ServiceLocator([
            'provider1' => function () {
                return $this->provider1;
            }
        ]);

        $this->registry = new FormTemplateDataProviderRegistry($providers);
    }

    public function testHasAndGetForKnownProvider(): void
    {
        self::assertTrue($this->registry->has('provider1'));
        self::assertSame($this->provider1, $this->registry->get('provider1'));
    }

    public function testHasForUnknownProvider(): void
    {
        self::assertFalse($this->registry->has('unknown'));
    }

    public function testGetForUnknownProvider(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown provider with alias "unknown".');

        $this->registry->get('unknown');
    }
}
