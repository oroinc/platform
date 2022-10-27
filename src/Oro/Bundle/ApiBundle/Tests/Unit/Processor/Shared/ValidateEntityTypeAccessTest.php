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
    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclGroupProviderInterface */
    private $aclGroupProvider;

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

    public function testProcessWhenAccessGrantedForManageableEntityWithoutConfigOfAclResource()
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

    public function testProcessWhenAccessDeniedForManageableEntityWithoutConfigOfAclResource()
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

    public function testProcessWhenAccessGrantedForEntityWithConfigOfAclResource()
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

    public function testProcessWhenAccessDeniedForEntityWithConfigOfAclResource()
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

    public function testForcePermissionUsage()
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

    public function testForcePermissionUsageWhenAclCheckIsDisabled()
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
