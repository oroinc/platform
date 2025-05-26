<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\EntityHolderInterface;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityObjectAccess;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidateEntityObjectAccessTest extends GetProcessorTestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private ValidateEntityObjectAccess $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new ValidateEntityObjectAccess(
            $this->authorizationChecker,
            'VIEW'
        );
    }

    public function testProcessWhenNoEntity(): void
    {
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGranted(): void
    {
        $entity = new Product();
        $config = new EntityDefinitionConfig();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(true);

        $this->context->setClassName($entity::class);
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access by "VIEW" permission to the entity.');

        $entity = new Product();
        $config = new EntityDefinitionConfig();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(false);

        $this->context->setClassName($entity::class);
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGrantedByAclResource(): void
    {
        $entity = new Product();
        $config = new EntityDefinitionConfig();
        $config->setAclResource('test_acl_resource');

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('test_acl_resource', self::identicalTo($entity))
            ->willReturn(true);

        $this->context->setClassName($entity::class);
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessDeniedByAclResource(): void
    {
        $this->expectException(AccessDeniedException::class);
        $entity = new Product();
        $config = new EntityDefinitionConfig();
        $config->setAclResource('test_acl_resource');

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('test_acl_resource', self::identicalTo($entity))
            ->willReturn(false);

        $this->context->setClassName($entity::class);
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessCheckDisabledBySettingEmptyAclResource(): void
    {
        $entity = new Product();
        $config = new EntityDefinitionConfig();
        $config->setAclResource('');

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setClassName($entity::class);
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGrantedForEntityHolderModel(): void
    {
        $entity = new Product();
        $config = new EntityDefinitionConfig();
        $model = $this->createMock(EntityHolderInterface::class);
        $model->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(true);

        $this->context->setClassName($entity::class);
        $this->context->setConfig($config);
        $this->context->setResult($model);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessDeniedForEntityHolderModel(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access by "VIEW" permission to the entity.');

        $entity = new Product();
        $config = new EntityDefinitionConfig();
        $model = $this->createMock(EntityHolderInterface::class);
        $model->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(false);

        $this->context->setClassName($entity::class);
        $this->context->setConfig($config);
        $this->context->setResult($model);
        $this->processor->process($this->context);
    }

    public function testProcessWhenEntityHolderModelDoesNotHaveEntity(): void
    {
        $config = new EntityDefinitionConfig();
        $model = $this->createMock(EntityHolderInterface::class);
        $model->expects(self::once())
            ->method('getEntity')
            ->willReturn(null);

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setClassName(Product::class);
        $this->context->setConfig($config);
        $this->context->setResult($model);
        $this->processor->process($this->context);
    }
}
