<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImapBundle\DependencyInjection\Compiler\Oauth2ManagerRegistryPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class Oauth2ManagerRegistryPassTest extends TestCase
{
    /** @var Oauth2ManagerRegistryPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new Oauth2ManagerRegistryPass();
    }

    public function testProcessNoMainService()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_imap.manager_registry.registry');

        $container->register('manager_1')
            ->addTag('oro_imap.oauth2_manager');
        $container->register('manager_2')
            ->addTag('oro_imap.oauth2_manager');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addManager', [new Reference('manager_1')]],
                ['addManager', [new Reference('manager_2')]]
            ],
            $registryDef->getMethodCalls()
        );
    }

    public function testProcessWhenNoManagers()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_imap.manager_registry.registry');

        $this->compiler->process($container);

        self::assertSame([], $registryDef->getMethodCalls());
    }
}
