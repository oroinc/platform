<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclGroupProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class AclGroupProvidersPassTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;

    private Definition $chainProvider;

    private AclGroupProvidersPass $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->chainProvider = $this->container->register('oro_security.acl.group_provider.chain');

        $this->compiler = new AclGroupProvidersPass();
    }

    public function testProcess(): void
    {
        $this->container->register('provider1')
            ->addTag('oro_security.acl.group_provider', ['alias' => 'alias1']);
        $this->container->register('provider2')
            ->addTag('oro_security.acl.group_provider', ['alias' => 'alias2']);
        // override by alias
        $this->container->register('provider3')
            ->addTag('oro_security.acl.group_provider', ['alias' => 'alias2', 'priority' => 10]);

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                new Reference('provider3'),
                new Reference('provider1')
            ],
            $this->chainProvider->getArgument('$providers')
        );
    }

    public function testProcessWithoutAlias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "alias" is required for "oro_security.acl.group_provider" tag. Service: "provider1".'
        );

        $this->container->register('provider1')
            ->addTag('oro_security.acl.group_provider');

        $this->compiler->process($this->container);
    }
}
