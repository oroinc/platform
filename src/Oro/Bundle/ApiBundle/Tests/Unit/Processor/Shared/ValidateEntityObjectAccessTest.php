<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityObjectAccess;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidateEntityObjectAccessTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var ValidateEntityObjectAccess */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new ValidateEntityObjectAccess(
            $this->authorizationChecker,
            'VIEW'
        );
    }

    public function testProcessWhenNoEntity()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGranted()
    {
        $entity = new Product();
        $config = new EntityDefinitionConfig();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(true);

        $this->context->setClassName(get_class($entity));
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessDenied()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access by "VIEW" permission to the entity.');

        $entity = new Product();
        $config = new EntityDefinitionConfig();

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($entity))
            ->willReturn(false);

        $this->context->setClassName(get_class($entity));
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessGrantedByAclResource()
    {
        $entity = new Product();
        $config = new EntityDefinitionConfig();
        $config->setAclResource('test_acl_resource');

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('test_acl_resource', self::identicalTo($entity))
            ->willReturn(true);

        $this->context->setClassName(get_class($entity));
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessDeniedByAclResource()
    {
        $this->expectException(AccessDeniedException::class);
        $entity = new Product();
        $config = new EntityDefinitionConfig();
        $config->setAclResource('test_acl_resource');

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('test_acl_resource', self::identicalTo($entity))
            ->willReturn(false);

        $this->context->setClassName(get_class($entity));
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAccessCheckDisabledBySettingEmptyAclResource()
    {
        $entity = new Product();
        $config = new EntityDefinitionConfig();
        $config->setAclResource('');

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setClassName(get_class($entity));
        $this->context->setConfig($config);
        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }
}
