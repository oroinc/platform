<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\InitializeEntityMapper;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use PHPUnit\Framework\MockObject\MockObject;

class InitializeEntityMapperTest extends FormProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private EntityInstantiator&MockObject $entityInstantiator;
    private EntityOverrideProviderRegistry&MockObject $entityOverrideProviderRegistry;
    private InitializeEntityMapper $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityInstantiator = $this->createMock(EntityInstantiator::class);
        $this->entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);

        $this->processor = new InitializeEntityMapper(
            $this->doctrineHelper,
            $this->entityInstantiator,
            $this->entityOverrideProviderRegistry
        );
    }

    public function testProcessWhenEntityMapperIsAlreadySet(): void
    {
        $entityMapper = $this->createMock(EntityMapper::class);

        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');

        $this->context->setEntityMapper($entityMapper);
        $this->processor->process($this->context);

        self::assertSame($entityMapper, $this->context->getEntityMapper());
    }

    public function testProcessForNotManageableEntity(): void
    {
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn(null);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertNull($this->context->getEntityMapper());
    }

    public function testProcessForManageableEntity(): void
    {
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertInstanceOf(EntityMapper::class, $this->context->getEntityMapper());
    }

    public function testProcessForApiResourceBasedOnManageableEntity(): void
    {
        $entityClass = Entity\UserProfile::class;
        $parentResourceClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentResourceClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($parentResourceClass);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertInstanceOf(EntityMapper::class, $this->context->getEntityMapper());
    }
}
