<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\DoctrineTypeMappingProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineTypeMappingProviderPassTest extends \PHPUnit\Framework\TestCase
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
        /* @var $compilerPass DoctrineTypeMappingProviderPass|\PHPUnit\Framework\MockObject\MockObject */
        $compilerPass = $this->getMockBuilder(DoctrineTypeMappingProviderPass::class)
            ->setMethods(['registerTaggedServices'])
            ->getMock();

        $compilerPass->expects($this->once())
            ->method('registerTaggedServices')
            ->with(
                $this->builder,
                DoctrineTypeMappingProviderPass::SERVICE_ID,
                DoctrineTypeMappingProviderPass::EXTENSION_TAG,
                'addExtension'
            );

        $compilerPass->process($this->builder);
    }
}
