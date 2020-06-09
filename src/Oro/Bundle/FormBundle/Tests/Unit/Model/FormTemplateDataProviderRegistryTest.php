<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class FormTemplateDataProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormTemplateDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider1;

    /** @var FormTemplateDataProviderRegistry */
    private $registry;

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

    public function testHasAndGetForKnownProvider()
    {
        self::assertTrue($this->registry->has('provider1'));
        self::assertSame($this->provider1, $this->registry->get('provider1'));
    }

    public function testHasForUnknownProvider()
    {
        self::assertFalse($this->registry->has('unknown'));
    }

    public function testGetForUnknownProvider()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown provider with alias "unknown".');

        $this->registry->get('unknown');
    }
}
