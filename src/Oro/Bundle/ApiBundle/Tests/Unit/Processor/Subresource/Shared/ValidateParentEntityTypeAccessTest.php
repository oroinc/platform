<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityTypeAccess;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidateParentEntityTypeAccessTest extends GetSubresourceProcessorTestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private DoctrineHelper&MockObject $doctrineHelper;
    private AclGroupProviderInterface&MockObject $aclGroupProvider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->aclGroupProvider = $this->createMock(AclGroupProviderInterface::class);

        $this->doctrineHelper->expects(self::any())
            ->method('getManageableEntityClass')
            ->willReturnArgument(0);
    }

    private function getProcessor(bool $forcePermissionUsage = false): ValidateParentEntityTypeAccess
    {
        return new ValidateParentEntityTypeAccess(
            $this->authorizationChecker,
            $this->doctrineHelper,
            $this->aclGroupProvider,
            'VIEW',
            $forcePermissionUsage
        );
    }

    public function testProcessWhenOperationAlreadyProcessed(): void
    {
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setProcessed(ValidateParentEntityTypeAccess::getOperationName('VIEW'));
        $this->context->setParentClassName(Product::class);
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->getProcessor()->process($this->context);
        self::assertTrue($this->context->isProcessed(ValidateParentEntityTypeAccess::getOperationName('VIEW')));
    }

    public function testProcessWhenAccessGrantedForManageableParentEntityWithoutConfigOfAclResource(): void
    {
        $parentClassName = Product::class;
        $parentConfig = new EntityDefinitionConfig();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', 'entity:' . $parentClassName)
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor()->process($this->context);
        self::assertTrue($this->context->isProcessed(ValidateParentEntityTypeAccess::getOperationName('VIEW')));
    }

    public function testProcessWhenAccessDeniedForManageableParentEntityWithoutConfigOfAclResource(): void
    {
        $this->expectException(AccessDeniedException::class);
        $parentClassName = Product::class;
        $parentConfig = new EntityDefinitionConfig();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', 'entity:' . $parentClassName)
            ->willReturn(false);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor()->process($this->context);
    }

    public function testProcessWhenAccessGrantedForParentEntityWithConfigOfAclResource(): void
    {
        $parentClassName = Product::class;
        $aclResource = 'acme_product_test';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource($aclResource);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor()->process($this->context);
        self::assertTrue($this->context->isProcessed(ValidateParentEntityTypeAccess::getOperationName('VIEW')));
    }

    public function testProcessWhenAccessDeniedForParentEntityWithConfigOfAclResource(): void
    {
        $this->expectException(AccessDeniedException::class);
        $parentClassName = Product::class;
        $aclResource = 'acme_product_test';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource($aclResource);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(false);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor()->process($this->context);
        self::assertTrue($this->context->isProcessed(ValidateParentEntityTypeAccess::getOperationName('VIEW')));
    }

    public function testForcePermissionUsage(): void
    {
        $parentClassName = Product::class;
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource('acme_product_test');

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', 'entity:' . $parentClassName)
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor(true)->process($this->context);
        self::assertTrue($this->context->isProcessed(ValidateParentEntityTypeAccess::getOperationName('VIEW')));
    }

    public function testForcePermissionUsageWhenAclCheckIsDisabled(): void
    {
        $parentClassName = Product::class;
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource(null);

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentConfig($parentConfig);
        $this->getProcessor(true)->process($this->context);
        self::assertTrue($this->context->isProcessed(ValidateParentEntityTypeAccess::getOperationName('VIEW')));
    }
}
