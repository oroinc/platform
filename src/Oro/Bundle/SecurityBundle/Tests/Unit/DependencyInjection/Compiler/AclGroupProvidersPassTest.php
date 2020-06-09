<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclGroupProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AclGroupProvidersPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $chainProvider;

    /** @var AclGroupProvidersPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->chainProvider = $this->container->register('oro_security.acl.group_provider.chain');

        $this->compiler = new AclGroupProvidersPass();
    }

    public function testProcess()
    {
        $this->container->register('provider1')
            ->addTag('oro_security.acl.group_provider', ['alias' => 'alias1']);
        $this->container->register('provider2')
            ->addTag('oro_security.acl.group_provider', ['alias' => 'alias2']);
        // override by alias
        $this->container->register('provider3')
            ->addTag('oro_security.acl.group_provider', ['alias' => 'alias2', 'priority' => 10]);

        $this->compiler->process($this->container);

        $this->assertEquals(
            [
                new Reference('provider3'),
                new Reference('provider1')
            ],
            $this->chainProvider->getArgument(0)
        );
    }

    public function testProcessWithoutAlias()
    {
        $this->expectException(\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "alias" is required for "oro_security.acl.group_provider" tag. Service: "provider1".'
        );

        $this->container->register('provider1')
            ->addTag('oro_security.acl.group_provider');

        $this->compiler->process($this->container);
    }
}
