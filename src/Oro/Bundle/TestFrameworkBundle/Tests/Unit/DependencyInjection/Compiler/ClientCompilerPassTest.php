<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\ClientCompilerPass;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClientCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ClientCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ClientCompilerPass();
    }

    public function testProcessNoProviderDefinition()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $clientDef = $container->register('test.client');

        $this->compiler->process($container);

        $this->assertEquals(Client::class, $clientDef->getClass());
    }
}
