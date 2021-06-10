<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\Compiler;

use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\TestSessionListenerCompilerPass;
use Oro\Bundle\TestFrameworkBundle\EventListener\TestSessionListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestSessionListenerCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var TestSessionListenerCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new TestSessionListenerCompilerPass();
    }

    public function testProcessWithoutDefinition()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $sessionListenerDef = $container->register('test.session.listener');

        $this->compiler->process($container);

        $this->assertEquals(TestSessionListener::class, $sessionListenerDef->getClass());
    }
}
