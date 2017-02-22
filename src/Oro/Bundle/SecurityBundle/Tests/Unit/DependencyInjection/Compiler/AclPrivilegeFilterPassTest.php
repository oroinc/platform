<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclPrivilegeFilterPass;

class AclPrivilegeFilterPassTest extends \PHPUnit_Framework_TestCase
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
        /* @var $compilerPass AclPrivilegeFilterPass|\PHPUnit_Framework_MockObject_MockObject */
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
