<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderResolver;
use Oro\Bundle\FormBundle\Provider\CallbackFormTemplateDataProvider;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;

class FormTemplateDataProviderResolverTest extends \PHPUnit\Framework\TestCase
{
    private FormTemplateDataProviderRegistry|\PHPUnit\Framework\MockObject\MockObject $formTemplateDataProviderRegistry;

    private FormTemplateDataProviderResolver $resolver;

    protected function setUp(): void
    {
        $this->formTemplateDataProviderRegistry = $this->createMock(FormTemplateDataProviderRegistry::class);

        $this->resolver = new FormTemplateDataProviderResolver($this->formTemplateDataProviderRegistry);
    }

    public function testResolveDefaultProvider(): void
    {
        $expectedProvider = $this->createMock(FormTemplateDataProviderInterface::class);

        $this->formTemplateDataProviderRegistry->expects(self::once())
            ->method('get')
            ->with(FormTemplateDataProviderRegistry::DEFAULT_PROVIDER_NAME)
            ->willReturn($expectedProvider);

        self::assertSame($expectedProvider, $this->resolver->resolve());
    }

    public function testResolveProvider(): void
    {
        $expectedProvider = $this->createMock(FormTemplateDataProviderInterface::class);

        $this->formTemplateDataProviderRegistry->expects(self::never())
            ->method('get')
            ->withAnyParameters();

        self::assertSame($expectedProvider, $this->resolver->resolve($expectedProvider));
    }

    public function testResolveCallableProvider(): void
    {
        $callableProvider = function () {
            return ['provider result'];
        };

        $expectedProvider = new CallbackFormTemplateDataProvider($callableProvider);

        $this->formTemplateDataProviderRegistry->expects(self::never())
            ->method('get')
            ->withAnyParameters();

        self::assertEquals($expectedProvider, $this->resolver->resolve($callableProvider));
    }

    public function testResolveAliasProvider(): void
    {
        $expectedProvider = $this->createMock(FormTemplateDataProviderInterface::class);

        $providerAlias = 'provider_alias';
        $this->formTemplateDataProviderRegistry->expects(self::once())
            ->method('get')
            ->with($providerAlias)
            ->willReturn($expectedProvider);

        self::assertSame($expectedProvider, $this->resolver->resolve($providerAlias));
    }
}
