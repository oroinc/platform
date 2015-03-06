<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ResourceMatcherVotersPass;

class ResourceMatcherVotersPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var ResourceMatcherVotersPass */
    protected $pass;

    protected function setUp()
    {
        $this->pass = new ResourceMatcherVotersPass();
    }

    protected function tearDown()
    {
        unset($this->pass);
    }

    public function testMatcherDefinitionNotFound()
    {
        $container = new ContainerBuilder();
        $this->pass->process($container);
    }

    public function testNoTaggedServicesFound()
    {
        $definition = new Definition();

        $container = new ContainerBuilder();
        $container->setDefinition('oro_layout.loader.resource_matcher', $definition);

        $this->pass->process($container);

        $this->assertEmpty($definition->getMethodCalls());
    }

    public function testFoundVoters()
    {
        $definition = new Definition();

        $container = new ContainerBuilder();
        $container->setDefinition('oro_layout.loader.resource_matcher', $definition);

        $voter1Def = new Definition();
        $voter1Def->addTag('layout.resource_matcher.voter', ['priority' => 100]);
        $container->setDefinition('voter1', $voter1Def);
        $voter2Def = new Definition();
        $voter2Def->addTag('layout.resource_matcher.voter');
        $container->setDefinition('voter2', $voter2Def);

        $this->pass->process($container);

        $methods = $definition->getMethodCalls();
        $this->assertCount(2, $methods);
        $this->assertEquals(['addVoter', [new Reference('voter1'), 100]], current($methods));
        $this->assertEquals(['addVoter', [new Reference('voter2'), 0]], next($methods));
    }
}
