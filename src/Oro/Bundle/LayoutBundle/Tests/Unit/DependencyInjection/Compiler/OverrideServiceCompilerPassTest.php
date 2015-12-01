<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OverrideServiceCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var OverrideServiceCompilerPass */
    protected $pass;

    protected function setUp()
    {
        $this->pass = new OverrideServiceCompilerPass();
    }

    protected function tearDown()
    {
        unset($this->pass);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $twigFormEngine = new Definition('\TwigFormEngineClass');
        $container->setDefinition('twig.form.engine', $twigFormEngine);

        $newTwigFormEngine = new Definition('\OroLayoutTwigFormEngineClass');
        $container->setDefinition('oro_layout.twig.form.engine', $newTwigFormEngine);

        $phpFormEngine = new Definition('\PhpFormEngineClass');
        $container->setDefinition('templating.form.engine', $phpFormEngine);

        $newPhpFormEngine = new Definition('\OroLayoutPhpFormEngineClass');
        $container->setDefinition('oro_layout.templating.form.engine', $newPhpFormEngine);

        $this->pass->process($container);

        $this->assertEquals($newTwigFormEngine, $container->getDefinition('twig.form.engine'));
        $this->assertEquals($newPhpFormEngine, $container->getDefinition('templating.form.engine'));
    }
}
