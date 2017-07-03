<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\EntityTypeSecurityCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class EntityTypeSecurityCheckTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    /** @var EntityTypeSecurityCheck */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new EntityTypeSecurityCheck(
            $this->doctrineHelper,
            $this->authorizationChecker,
            'VIEW'
        );
    }

    public function testProcessWhenAccessGrantedForManageableEntityWithoutConfigOfAclResource()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $config = new EntityDefinitionConfig();

        $this->authorizationChecker->expects($this->once())
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

        $this->authorizationChecker->expects($this->once())
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

        $this->authorizationChecker->expects($this->once())
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

        $this->authorizationChecker->expects($this->once())
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

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testForcePermissionUsage()
    {
        $this->processor = new EntityTypeSecurityCheck(
            $this->doctrineHelper,
            $this->authorizationChecker,
            'VIEW',
            true
        );

        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $config = new EntityDefinitionConfig();
        $config->setAclResource('acme_product_test');

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $className))
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testForcePermissionUsageWhenAclCheckIsDisabled()
    {
        $this->processor = new EntityTypeSecurityCheck(
            $this->doctrineHelper,
            $this->authorizationChecker,
            'VIEW',
            true
        );

        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $config = new EntityDefinitionConfig();
        $config->setAclResource(null);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }
}
