<?php

namespace Oro\Bundle\FormBundle\Tests\Unit;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler;
use Oro\Bundle\FormBundle\OroFormBundle;
use Oro\Component\DependencyInjection\Compiler\TaggedServiceLinkRegistryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroFormBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
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
                    Compiler\FormGuesserCompilerPass::class
                )
            );

        $containerBuilder->expects($this->at(2))
            ->method('addCompilerPass')
            ->with(
                new TaggedServiceLinkRegistryCompilerPass(
                    OroFormBundle::FORM_TEMPLATE_DATA_PROVIDER_TAG,
                    'oro_form.registry.form_template_data_provider'
                )
            );

        $containerBuilder->expects($this->at(3))
            ->method('addCompilerPass')
            ->with(
                new TaggedServiceLinkRegistryCompilerPass(
                    OroFormBundle::FORM_HANDLER_TAG,
                    'oro_form.registry.form_handler'
                )
            );

        $bundle = new OroFormBundle();
        $bundle->build($containerBuilder);
    }
}
