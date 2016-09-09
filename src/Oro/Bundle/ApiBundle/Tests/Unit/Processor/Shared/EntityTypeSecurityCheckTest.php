<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\EntityTypeSecurityCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class EntityTypeSecurityCheckTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var EntityTypeSecurityCheck */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new EntityTypeSecurityCheck($this->doctrineHelper, $this->securityFacade, 'VIEW');
    }

    public function testProcessWhenAccessGrantedForManageableEntityWithoutConfigOfAclResource()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $config = new EntityDefinitionConfig();

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $className))
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDeniedForManageableEntityWithoutConfigOfAclResource()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $config = new EntityDefinitionConfig();

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $className))
            ->willReturn(false);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGrantedForEntityWithConfigOfAclResource()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $aclResource = 'acme_product_test';
        $config = new EntityDefinitionConfig();
        $config->setAclResource($aclResource);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDeniedForEntityWithConfigOfAclResource()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $aclResource = 'acme_product_test';
        $config = new EntityDefinitionConfig();
        $config->setAclResource($aclResource);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(false);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testAccessShouldBeAlwaysGrantedForNotManageableEntityWithoutConfigOfAclResource()
    {
        $className = 'Test\Class';
        $config = new EntityDefinitionConfig();

        $this->notManageableClassNames = [$className];

        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }
}
