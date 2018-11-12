<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ButtonProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ButtonProviderPassTest extends \PHPUnit\Framework\TestCase
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
        /* @var $compilerPass ButtonProviderPass|\PHPUnit\Framework\MockObject\MockObject */
        $compilerPass = $this->getMockBuilder(ButtonProviderPass::class)
            ->setMethods(['registerTaggedServices'])
            ->getMock();

        $compilerPass->expects($this->once())
            ->method('registerTaggedServices')
            ->with(
                $this->builder,
                ButtonProviderPass::SERVICE_ID,
                ButtonProviderPass::EXTENSION_TAG,
                'addExtension'
            );

        $compilerPass->process($this->builder);
    }
}
