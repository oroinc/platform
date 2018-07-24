<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ParentEntityTypeSecurityCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ParentEntityTypeSecurityCheckTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclGroupProviderInterface */
    private $aclGroupProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->aclGroupProvider = $this->createMock(AclGroupProviderInterface::class);
    }

    /**
     * @param bool $forcePermissionUsage
     *
     * @return ParentEntityTypeSecurityCheck
     */
    private function getProcessor($forcePermissionUsage = false)
    {
        return new ParentEntityTypeSecurityCheck(
            $this->doctrineHelper,
            $this->authorizationChecker,
            $this->aclGroupProvider,
            'VIEW',
            $forcePermissionUsage
        );
    }

    public function testProcessWhenAccessGrantedForManageableParentEntityWithoutConfigOfAclResource()
    {
        $parentClassName = Product::class;
        $parentConfig = new EntityDefinitionConfig();
        $aclGroup = 'test';

        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn($aclGroup);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $aclGroup . '@' . $parentClassName))
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor()->process($this->context);
    }

    public function testProcessWhenAccessGrantedForManageableParentEntityWithoutConfigOfAclResourceAndDefaultAclGroup()
    {
        $parentClassName = Product::class;
        $parentConfig = new EntityDefinitionConfig();
        $aclGroup = '';

        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn($aclGroup);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $parentClassName))
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor()->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDeniedForManageableParentEntityWithoutConfigOfAclResource()
    {
        $parentClassName = Product::class;
        $parentConfig = new EntityDefinitionConfig();
        $aclGroup = 'test';

        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn($aclGroup);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $aclGroup . '@' . $parentClassName))
            ->willReturn(false);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor()->process($this->context);
    }

    public function testProcessWhenAccessGrantedForParentEntityWithConfigOfAclResource()
    {
        $parentClassName = Product::class;
        $aclResource = 'acme_product_test';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource($aclResource);

        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor()->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDeniedForParentEntityWithConfigOfAclResource()
    {
        $parentClassName = Product::class;
        $aclResource = 'acme_product_test';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource($aclResource);

        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(false);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor()->process($this->context);
    }

    public function testAccessShouldBeAlwaysGrantedForNotManageableParentEntityWithoutConfigOfAclResource()
    {
        $parentClassName = 'Test\Class';
        $parentConfig = new EntityDefinitionConfig();

        $this->notManageableClassNames = [$parentClassName];

        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor()->process($this->context);
    }

    public function testForcePermissionUsage()
    {
        $parentClassName = Product::class;
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource('acme_product_test');
        $aclGroup = 'test';

        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn($aclGroup);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $aclGroup . '@' . $parentClassName))
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor(true)->process($this->context);
    }

    public function testForcePermissionUsageWhenAclCheckIsDisabled()
    {
        $parentClassName = Product::class;
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource(null);

        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor(true)->process($this->context);
    }
}
