<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationContextResolverPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TranslationContextResolverPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
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
        /* @var $compilerPass TranslationContextResolverPass|\PHPUnit\Framework\MockObject\MockObject */
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
