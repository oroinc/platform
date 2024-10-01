<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\EntityHolderInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityObjectAccess;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidateParentEntityObjectAccessTest extends ChangeRelationshipProcessorTestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ValidateParentEntityObjectAccess */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new ValidateParentEntityObjectAccess(
            $this->authorizationChecker,
            'VIEW'
        );
    }

    public function testProcessWhenNoParentEntity(): void
    {
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGranted(): void
    {
        $parentEntity = new Product();
        $parentConfig = new EntityDefinitionConfig();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($parentEntity))
            ->willReturn(true);

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access by "VIEW" permission to the parent entity.');

        $parentEntity = new Product();
        $parentConfig = new EntityDefinitionConfig();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($parentEntity))
            ->willReturn(false);

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGrantedByAclResource(): void
    {
        $parentEntity = new Product();
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource('test_acl_resource');

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('test_acl_resource', self::identicalTo($parentEntity))
            ->willReturn(true);

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessDeniedByAclResource(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access by "VIEW" permission to the parent entity.');

        $parentEntity = new Product();
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource('test_acl_resource');

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('test_acl_resource', self::identicalTo($parentEntity))
            ->willReturn(false);

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessCheckDisabledBySettingEmptyAclResource(): void
    {
        $parentEntity = new Product();
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setAclResource('');

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGrantedForEntityHolderModel(): void
    {
        $parentEntity = new Product();
        $parentConfig = new EntityDefinitionConfig();
        $parentModel = $this->createMock(EntityHolderInterface::class);
        $parentModel->expects(self::once())
            ->method('getEntity')
            ->willReturn($parentEntity);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($parentEntity))
            ->willReturn(true);

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentEntity($parentModel);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessDeniedForEntityHolderModel(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access by "VIEW" permission to the parent entity.');

        $parentEntity = new Product();
        $parentConfig = new EntityDefinitionConfig();
        $parentModel = $this->createMock(EntityHolderInterface::class);
        $parentModel->expects(self::once())
            ->method('getEntity')
            ->willReturn($parentEntity);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($parentEntity))
            ->willReturn(false);

        $this->context->setParentClassName(get_class($parentEntity));
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentEntity($parentModel);
        $this->processor->process($this->context);
    }

    public function testProcessWhenEntityHolderModelDoesNotHaveEntity(): void
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentModel = $this->createMock(EntityHolderInterface::class);
        $parentModel->expects(self::once())
            ->method('getEntity')
            ->willReturn(null);

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setParentClassName(Product::class);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentEntity($parentModel);
        $this->processor->process($this->context);
    }
}
