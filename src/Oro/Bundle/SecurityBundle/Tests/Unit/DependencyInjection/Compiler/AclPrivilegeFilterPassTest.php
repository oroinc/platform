<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclPrivilegeFilterPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AclPrivilegeFilterPassTest extends \PHPUnit\Framework\TestCase
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
        /* @var $compilerPass AclPrivilegeFilterPass|\PHPUnit\Framework\MockObject\MockObject */
        $compilerPass = $this->getMockBuilder(AclPrivilegeFilterPass::class)
            ->setMethods(['registerTaggedServices'])
            ->getMock();

        $compilerPass->expects($this->once())
            ->method('registerTaggedServices')
            ->with(
                $this->builder,
                AclPrivilegeFilterPass::SERVICE_ID,
                AclPrivilegeFilterPass::EXTENSION_TAG,
                'addConfigurableFilter'
            );

        $compilerPass->process($this->builder);
    }
}
