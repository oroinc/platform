<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ParentEntityTypeSecurityCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;

class ParentEntityTypeSecurityCheckTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var ParentEntityTypeSecurityCheck */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new ParentEntityTypeSecurityCheck($this->doctrineHelper, $this->securityFacade, 'VIEW');
    }

    public function testProcessWhenAccessGrantedForManageableParentEntityWithoutConfigOfAclResource()
    {
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $parentConfig = new EntityDefinitionConfig();

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $parentClassName))
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDeniedForManageableParentEntityWithoutConfigOfAclResource()
    {
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $parentConfig = new EntityDefinitionConfig();

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $parentClassName))
            ->willReturn(false);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGrantedForParentEntityWithConfigOfAclResource()
    {
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $aclResource = 'acme_product_test';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource($aclResource);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDeniedForParentEntityWithConfigOfAclResource()
    {
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $aclResource = 'acme_product_test';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource($aclResource);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(false);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
    }

    public function testAccessShouldBeAlwaysGrantedForNotManageableParentEntityWithoutConfigOfAclResource()
    {
        $parentClassName = 'Test\Class';
        $parentConfig = new EntityDefinitionConfig();

        $this->notManageableClassNames = [$parentClassName];

        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
    }
}
