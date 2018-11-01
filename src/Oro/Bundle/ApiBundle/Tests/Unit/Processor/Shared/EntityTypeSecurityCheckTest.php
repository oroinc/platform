<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\EntityTypeSecurityCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EntityTypeSecurityCheckTest extends GetListProcessorOrmRelatedTestCase
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
     * @return EntityTypeSecurityCheck
     */
    private function getProcessor($forcePermissionUsage = false)
    {
        return new EntityTypeSecurityCheck(
            $this->doctrineHelper,
            $this->authorizationChecker,
            $this->aclGroupProvider,
            'VIEW',
            $forcePermissionUsage
        );
    }

    public function testProcessWhenAccessGrantedForManageableEntityWithoutConfigOfAclResource()
    {
        $className = Product::class;
        $config = new EntityDefinitionConfig();
        $aclGroup = 'test';

        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn($aclGroup);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $aclGroup . '@' . $className))
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor()->process($this->context);
    }

    public function testProcessWhenAccessGrantedForManageableEntityWithoutConfigOfAclResourceAndDefaultAclGroup()
    {
        $className = Product::class;
        $config = new EntityDefinitionConfig();
        $aclGroup = '';

        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn($aclGroup);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $className))
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor()->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDeniedForManageableEntityWithoutConfigOfAclResource()
    {
        $className = Product::class;
        $config = new EntityDefinitionConfig();
        $aclGroup = 'test';

        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn($aclGroup);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $aclGroup . '@' . $className))
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

        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor()->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDeniedForEntityWithConfigOfAclResource()
    {
        $className = Product::class;
        $aclResource = 'acme_product_test';
        $config = new EntityDefinitionConfig();
        $config->setAclResource($aclResource);

        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(false);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor()->process($this->context);
    }

    public function testAccessShouldBeAlwaysGrantedForNotManageableEntityWithoutConfigOfAclResource()
    {
        $className = 'Test\Class';
        $config = new EntityDefinitionConfig();

        $this->notManageableClassNames = [$className];

        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor()->process($this->context);
    }

    public function testForcePermissionUsage()
    {
        $className = Product::class;
        $config = new EntityDefinitionConfig();
        $config->setAclResource('acme_product_test');
        $aclGroup = 'test';

        $this->aclGroupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn($aclGroup);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $aclGroup . '@' . $className))
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

        $this->aclGroupProvider->expects(self::never())
            ->method('getGroup');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->getProcessor(true)->process($this->context);
    }
}
