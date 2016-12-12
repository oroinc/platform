<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ButtonProviderPass;

class ButtonProviderPassTest extends \PHPUnit_Framework_TestCase
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
        /* @var $compilerPass ButtonProviderPass|\PHPUnit_Framework_MockObject_MockObject */
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
