<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnerMetadataProvidersPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class OwnerMetadataProvidersPassTest extends TestCase
{
    private ContainerBuilder $container;
    private Definition $chainProvider;
    private OwnerMetadataProvidersPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->chainProvider = $this->container->register('oro_security.owner.metadata_provider.chain');

        $this->compiler = new OwnerMetadataProvidersPass();
    }

    public function testProcess(): void
    {
        $this->container->register('provider1')
            ->addTag('oro_security.owner.metadata_provider', ['alias' => 'alias1']);
        $this->container->register('provider2')
            ->addTag('oro_security.owner.metadata_provider', ['alias' => 'alias2']);
        // override by alias
        $this->container->register('provider3')
            ->addTag('oro_security.owner.metadata_provider', ['alias' => 'alias2', 'priority' => 10]);

        $this->compiler->process($this->container);

        $this->assertEquals(
            [
                ['addProvider', ['alias2', new Reference('provider3')]],
                ['addProvider', ['alias1', new Reference('provider1')]]
            ],
            $this->chainProvider->getMethodCalls()
        );
    }

    public function testProcessWithoutAlias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "alias" is required for "oro_security.owner.metadata_provider" tag. Service: "provider1".'
        );

        $this->container->register('provider1')
            ->addTag('oro_security.owner.metadata_provider');

        $this->compiler->process($this->container);
    }
}
