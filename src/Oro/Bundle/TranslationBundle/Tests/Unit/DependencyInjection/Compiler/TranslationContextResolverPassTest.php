<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationContextResolverPass;

class TranslationContextResolverPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->builder = $this->getMockBuilder(ContainerBuilder::class)->getMock();
    }

    public function testProcess()
    {
        /* @var $compilerPass TranslationContextResolverPass|\PHPUnit_Framework_MockObject_MockObject */
        $compilerPass = $this->getMockBuilder(TranslationContextResolverPass::class)
            ->setMethods(['registerTaggedServices'])
            ->getMock();

        $compilerPass->expects($this->once())
            ->method('registerTaggedServices')
            ->with(
                $this->builder,
                TranslationContextResolverPass::SERVICE_ID,
                TranslationContextResolverPass::EXTENSION_TAG,
                'addExtension'
            );

        $compilerPass->process($this->builder);
    }
}
