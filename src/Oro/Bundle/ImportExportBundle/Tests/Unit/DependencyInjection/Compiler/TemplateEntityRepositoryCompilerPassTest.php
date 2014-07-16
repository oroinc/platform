<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\TemplateEntityRepositoryCompilerPass;
use Symfony\Component\DependencyInjection\Reference;

class TemplateEntityRepositoryCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $taggedServices = array(
            'oro_test.foo_import_fixture' => array(
                array(
                    'name' => TemplateEntityRepositoryCompilerPass::TEMPLATE_FIXTURE_TAG
                )
            )
        );
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(TemplateEntityRepositoryCompilerPass::TEMPLATE_MANAGER_KEY)
            ->will($this->returnValue(true));
        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(TemplateEntityRepositoryCompilerPass::TEMPLATE_MANAGER_KEY)
            ->will($this->returnValue($definition));
        $containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(TemplateEntityRepositoryCompilerPass::TEMPLATE_FIXTURE_TAG)
            ->will($this->returnValue($taggedServices));
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addEntityRepository', array(new Reference('oro_test.foo_import_fixture')));

        $pass = new TemplateEntityRepositoryCompilerPass();
        $pass->process($containerBuilder);
    }
}
