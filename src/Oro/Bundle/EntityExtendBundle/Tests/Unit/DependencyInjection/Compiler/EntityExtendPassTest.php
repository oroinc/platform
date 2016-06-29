<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityExtendPass;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;

use Oro\Component\Config\CumulativeResourceManager;

class EntityExtendPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1->getName() => get_class($bundle1), $bundle2->getName() => get_class($bundle2)]);

        $fieldTypeHelperDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $container->expects($this->at(1))
            ->method('hasDefinition')
            ->with(EntityExtendPass::FIELD_TYPE_HELPER_SERVICE_ID)
            ->will($this->returnValue(true));
        $container->expects($this->at(2))
            ->method('getDefinition')
            ->with(EntityExtendPass::FIELD_TYPE_HELPER_SERVICE_ID)
            ->will($this->returnValue($fieldTypeHelperDef));

        $fieldTypeHelperDef->expects($this->once())
            ->method('replaceArgument')
            ->with(
                0,
                [
                    'enum'      => 'manyToOne',
                    'multiEnum' => 'manyToMany'
                ]
            );

        $loaderDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->at(3))
            ->method('hasDefinition')
            ->with('validator.builder')
            ->will($this->returnValue(true));
        $container->expects($this->at(4))
            ->method('getDefinition')
            ->with('validator.builder')
            ->will($this->returnValue($loaderDef));


        $compiler = new EntityExtendPass();
        $compiler->process($container);
    }
}
