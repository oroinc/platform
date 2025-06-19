<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Criteria as CommonCriteria;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\InitializeCriteria;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class InitializeCriteriaTest extends GetListProcessorOrmRelatedTestCase
{
    private EntityClassResolver $entityClassResolver;
    private InitializeCriteria $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityClassResolver = new EntityClassResolver($this->doctrine);

        $this->processor = new InitializeCriteria(
            $this->doctrineHelper,
            $this->entityClassResolver
        );
    }

    public function testProcessWhenDataAreAlreadyLoaded(): void
    {
        $this->context->setResult([]);
        $this->processor->process($this->context);

        self::assertNull($this->context->getCriteria());
    }

    public function testProcessWhenCriteriaIsAlreadyInitialized(): void
    {
        $criteria = new Criteria($this->entityClassResolver);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        self::assertSame($criteria, $this->context->getCriteria());
    }

    public function testProcessForNotManageableEntity(): void
    {
        $entityClass = 'Test\Class';
        $this->notManageableClassNames = [$entityClass];

        $this->context->setClassName($entityClass);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        self::assertInstanceOf(
            CommonCriteria::class,
            $this->context->getCriteria()
        );
    }

    public function testProcessForManageableEntity(): void
    {
        $entityClass = Entity\Product::class;

        $this->context->setClassName($entityClass);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        self::assertEquals(
            new Criteria($this->entityClassResolver),
            $this->context->getCriteria()
        );
    }

    public function testProcessForResourceBasedOnManageableEntity(): void
    {
        $entityClass = Entity\UserProfile::class;
        $parentResourceClass = Entity\User::class;
        $this->notManageableClassNames = [$entityClass];

        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentResourceClass);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertEquals(
            new Criteria($this->entityClassResolver),
            $this->context->getCriteria()
        );
    }

    public function testProcessForResourceBasedOnNotManageableEntity(): void
    {
        $entityClass = 'Test\Class';
        $parentResourceClass = 'Test\ParentClass';
        $this->notManageableClassNames = [$entityClass, $parentResourceClass];

        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentResourceClass);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertInstanceOf(
            CommonCriteria::class,
            $this->context->getCriteria()
        );
    }
}
