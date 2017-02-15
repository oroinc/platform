<?php

namespace Oro\Bundle\FormBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler as Compiler;
use Oro\Bundle\FormBundle\OroFormBundle;

class OroFormBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['addCompilerPass'])
            ->getMock();

        $containerBuilder->expects($this->at(0))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\AutocompleteCompilerPass::class
                )
            );

        $containerBuilder->expects($this->at(1))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\FormCompilerPass::class
                )
            );

        $containerBuilder->expects($this->at(2))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\FormGuesserCompilerPass::class
                )
            );

        $containerBuilder->expects($this->at(3))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\FormTemplateDataProviderCompilerPass::class
                )
            );

        $containerBuilder->expects($this->at(4))
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(
                    Compiler\FormHandlerCompilerPass::class
                )
            );

        $bundle = new OroFormBundle();
        $bundle->build($containerBuilder);
    }
}
