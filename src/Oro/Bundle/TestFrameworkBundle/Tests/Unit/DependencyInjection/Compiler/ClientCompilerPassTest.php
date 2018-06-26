<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\ClientCompilerPass;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ClientCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ClientCompilerPass
     */
    protected $compilerPass;

    /**
     * @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $container;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerBuilder::class);

        $this->compilerPass = new ClientCompilerPass();
    }

    public function testProcessNoProviderDefinition()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(ClientCompilerPass::CLIENT_SERVICE)
            ->willReturn(false);

        $this->container->expects($this->never())
            ->method('getDefinition')
            ->with(ClientCompilerPass::CLIENT_SERVICE);

        $this->compilerPass->process($this->container);
    }

    public function testProcess()
    {
        $client = new Definition();

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(ClientCompilerPass::CLIENT_SERVICE)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with(ClientCompilerPass::CLIENT_SERVICE)
            ->willReturn($client);

        $this->compilerPass->process($this->container);

        $this->assertEquals(Client::class, $client->getClass());
    }
}
