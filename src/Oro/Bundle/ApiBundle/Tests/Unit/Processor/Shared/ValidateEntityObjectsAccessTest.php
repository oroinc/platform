<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\EntityHolderInterface;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityObjectAccess;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityObjectsAccess;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList\DeleteListProcessorTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ValidateEntityObjectsAccessTest extends DeleteListProcessorTestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ValidateEntityObjectAccess */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new ValidateEntityObjectsAccess(
            $this->authorizationChecker,
            'DELETE'
        );
    }

    private function getModel(?object $entity): EntityHolderInterface
    {
        $model = $this->createMock(EntityHolderInterface::class);
        $model->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);

        return $model;
    }

    public function testProcessWhenNoEntities(): void
    {
        $this->processor->process($this->context);
    }

    public function testProcessShouldFilterEntitiesToWhichAccessDenied(): void
    {
        $config = new EntityDefinitionConfig();
        $entities = [new Product(), new Product(), new Product()];

        $this->authorizationChecker->expects(self::exactly(3))
            ->method('isGranted')
            ->withConsecutive(
                ['DELETE', self::identicalTo($entities[0])],
                ['DELETE', self::identicalTo($entities[1])],
                ['DELETE', self::identicalTo($entities[2])]
            )
            ->willReturnOnConsecutiveCalls(true, false, true);

        $this->context->setConfig($config);
        $this->context->setResult($entities);
        $this->processor->process($this->context);

        self::assertSame([$entities[0], $entities[2]], $this->context->getResult());
    }

    public function testProcessShouldFilterEntitiesToWhichAccessDeniedByAclResource(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setAclResource('test_acl_resource');
        $entities = [new Product(), new Product(), new Product()];

        $this->authorizationChecker->expects(self::exactly(3))
            ->method('isGranted')
            ->withConsecutive(
                ['test_acl_resource', self::identicalTo($entities[0])],
                ['test_acl_resource', self::identicalTo($entities[1])],
                ['test_acl_resource', self::identicalTo($entities[2])]
            )
            ->willReturnOnConsecutiveCalls(true, false, true);

        $this->context->setConfig($config);
        $this->context->setResult($entities);
        $this->processor->process($this->context);

        self::assertSame([$entities[0], $entities[2]], $this->context->getResult());
    }

    public function testProcessWhenAccessCheckDisabledBySettingEmptyAclResource(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setAclResource('');
        $entities = [new Product(), new Product(), new Product()];

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $this->context->setConfig($config);
        $this->context->setResult($entities);
        $this->processor->process($this->context);

        self::assertSame($entities, $this->context->getResult());
    }

    public function testProcessShouldFilterEntitiesToWhichAccessDeniedFoEmailHolderModels(): void
    {
        $config = new EntityDefinitionConfig();
        $entities = [new Product(), new Product(), new Product()];
        $models = [
            $this->getModel($entities[0]),
            $this->getModel($entities[1]),
            $this->getModel($entities[2]),
            $this->getModel(null)
        ];

        $this->authorizationChecker->expects(self::exactly(3))
            ->method('isGranted')
            ->withConsecutive(
                ['DELETE', self::identicalTo($entities[0])],
                ['DELETE', self::identicalTo($entities[1])],
                ['DELETE', self::identicalTo($entities[2])]
            )
            ->willReturnOnConsecutiveCalls(true, false, true);

        $this->context->setConfig($config);
        $this->context->setResult($models);
        $this->processor->process($this->context);

        self::assertSame([$models[0], $models[2], $models[3]], $this->context->getResult());
    }
}
