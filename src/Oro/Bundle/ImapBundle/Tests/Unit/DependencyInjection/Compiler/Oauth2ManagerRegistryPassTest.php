<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImapBundle\DependencyInjection\Compiler\Oauth2ManagerRegistryPass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class Oauth2ManagerRegistryPassTest extends TestCase
{
    /** @var Oauth2ManagerRegistryPass */
    protected $compilerPass;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->compilerPass = new Oauth2ManagerRegistryPass();
    }

    public function testProcessNoMainService()
    {
        /** @var ContainerBuilder|MockObject $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_imap.manager_registry.registry')
            ->willReturn(false);

        $containerBuilder->expects($this->never())
            ->method('getDefinition');
        $containerBuilder->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($containerBuilder);
    }

    public function testProcess()
    {
        $definition = new Definition();

        /** @var ContainerBuilder|MockObject $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_imap.manager_registry.registry')
            ->willReturn(true);

        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with('oro_imap.manager_registry.registry')
            ->willReturn($definition);

        $containerBuilder->expects($this->exactly(1))
            ->method('findTaggedServiceIds')
            ->willReturnMap(
                [
                    [
                        'oro_imap.oauth2_manager',
                        false,
                        ['first_service' => [], 'second_service' => []]
                    ],
                ]
            );

        $this->compilerPass->process($containerBuilder);

        $calls = $definition->getMethodCalls();
        $this->assertCount(2, $calls);

        $this->assertEquals('addManager', $calls[0][0]);
        $this->assertEquals('addManager', $calls[1][0]);
        $this->assertEquals(new Reference('first_service'), $calls[0][1][0]);
        $this->assertEquals(new Reference('second_service'), $calls[1][1][0]);
    }
}
