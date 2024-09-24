<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityTypeAccess;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidateEntityTypeAccessTest extends GetListProcessorTestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AclGroupProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $aclGroupProvider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->aclGroupProvider = $this->createMock(AclGroupProviderInterface::class);

        $this->doctrineHelper->expects(self::any())
            ->method('getManageableEntityClass')
            ->willReturnCallback(function ($className) {
                return $className;
            });
    }

    private function getProcessor(bool $forcePermissionUsage = false): ValidateEntityTypeAccess
    {
        return new ValidateEntityTypeAccess(
            $this->authorizationChecker,
            $this->doctrineHelper,
            $this->aclGroupProvider,
            'VIEW',
            $forcePermissionUsage
        );
    }

    public function testProcessWhenAccessGrantedForManageableEntityWithoutConfigOfAclResource(): void
    {
        $className = Product::class;
        $config = new EntityDefinitionConfig();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', 'entity:' . $className)
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor()->process($this->context);
    }

    public function testProcessWhenAccessDeniedForManageableEntityWithoutConfigOfAclResource(): void
    {
        $this->expectException(AccessDeniedException::class);
        $className = Product::class;
        $config = new EntityDefinitionConfig();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', 'entity:' . $className)
            ->willReturn(false);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor()->process($this->context);
    }

    public function testProcessWhenAccessGrantedForEntityWithConfigOfAclResource(): void
    {
        $className = Product::class;
        $aclResource = 'acme_product_test';
        $config = new EntityDefinitionConfig();
        $config->setAclResource($aclResource);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor()->process($this->context);
    }

    public function testProcessWhenAccessDeniedForEntityWithConfigOfAclResource(): void
    {
        $this->expectException(AccessDeniedException::class);
        $className = Product::class;
        $aclResource = 'acme_product_test';
        $config = new EntityDefinitionConfig();
        $config->setAclResource($aclResource);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(false);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor()->process($this->context);
    }

    public function testForcePermissionUsage(): void
    {
        $className = Product::class;
        $config = new EntityDefinitionConfig();
        $config->setAclResource('acme_product_test');

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', 'entity:' . $className)
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor(true)->process($this->context);
    }

    public function testForcePermissionUsageWhenAclCheckIsDisabled(): void
    {
        $className = Product::class;
        $config = new EntityDefinitionConfig();
        $config->setAclResource(null);

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor(true)->process($this->context);
    }
}
