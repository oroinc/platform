<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationStrategyPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TranslationStrategyPassTest extends \PHPUnit\Framework\TestCase
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
        /** @var $compilerPass TranslationStrategyPass|\PHPUnit\Framework\MockObject\MockObject */
        $compilerPass = $this->getMockBuilder(TranslationStrategyPass::class)
            ->setMethods(['registerTaggedServices'])
            ->getMock();

        $compilerPass->expects($this->once())
            ->method('registerTaggedServices')
            ->with(
                $this->builder,
                TranslationStrategyPass::SERVICE_ID,
                TranslationStrategyPass::EXTENSION_TAG,
                'addStrategy'
            );

        $compilerPass->process($this->builder);
    }
}
