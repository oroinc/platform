<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\TemplateFixtureCompilerPass;
use Symfony\Component\DependencyInjection\Reference;

class TemplateFixtureCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $taggedServices = array(
            'oro_test.foo_import_fixture' => array(
                array(
                    'name' => TemplateFixtureCompilerPass::TEMPLATE_FIXTURE_TAG,
                    'entity' => 'FooEntity',
                )
            )
        );
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(TemplateFixtureCompilerPass::TEMPLATE_REGISTRY_KEY)
            ->will($this->returnValue(true));
        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(TemplateFixtureCompilerPass::TEMPLATE_REGISTRY_KEY)
            ->will($this->returnValue($definition));
        $containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(TemplateFixtureCompilerPass::TEMPLATE_FIXTURE_TAG)
            ->will($this->returnValue($taggedServices));
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addEntityFixture', array('FooEntity',  new Reference('oro_test.foo_import_fixture')));

        $pass = new TemplateFixtureCompilerPass();
        $pass->process($containerBuilder);
    }
}
