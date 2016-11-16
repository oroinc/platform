<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationStrategyPass;

class TranslationStrategyPassTest extends \PHPUnit_Framework_TestCase
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
        /** @var $compilerPass TranslationStrategyPass|\PHPUnit_Framework_MockObject_MockObject */
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
