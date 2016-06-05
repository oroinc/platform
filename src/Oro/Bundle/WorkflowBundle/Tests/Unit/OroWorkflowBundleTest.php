<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\WorkflowBundle\OroWorkflowBundle;

class OroWorkflowBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('addCompilerPass'))
            ->getMock();

        $containerBuilder->expects($this->at(0))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    'Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddConditionAndActionCompilerPass'
                )
            );

        $containerBuilder->expects($this->at(1))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    'Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddAttributeNormalizerCompilerPass'
                )
            );

        $containerBuilder->expects($this->at(2))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    'Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\WorkflowChangesEventsCompilerPass'
                )
            );

        $bundle = new OroWorkflowBundle();
        $bundle->build($containerBuilder);
    }
}
