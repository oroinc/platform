<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\EntityObjectSecurityCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EntityObjectSecurityCheckTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var EntityObjectSecurityCheck */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new EntityObjectSecurityCheck(
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

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDenied()
    {
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

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWhenAccessDeniedByAclResource()
    {
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
