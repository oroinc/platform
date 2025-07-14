<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\ClientCompilerPass;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClientCompilerPassTest extends TestCase
{
    private ClientCompilerPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new ClientCompilerPass();
    }

    public function testProcessNoProviderDefinition(): void
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $clientDef = $container->register('test.client');

        $this->compiler->process($container);

        $this->assertEquals(Client::class, $clientDef->getClass());
    }
}
