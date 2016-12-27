<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationPackagesProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TranslationPackagesProviderPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();

        /** @var $compilerPass TranslationPackagesProviderPass|\PHPUnit_Framework_MockObject_MockObject */
        $compilerPass = $this->getMockBuilder(TranslationPackagesProviderPass::class)
            ->setMethods(['registerTaggedServices'])
            ->getMock();

        $compilerPass->expects($this->once())
            ->method('registerTaggedServices')
            ->with(
                $builder,
                TranslationPackagesProviderPass::SERVICE_ID,
                TranslationPackagesProviderPass::EXTENSION_TAG,
                'addExtension'
            );

        $compilerPass->process($builder);
    }
}
