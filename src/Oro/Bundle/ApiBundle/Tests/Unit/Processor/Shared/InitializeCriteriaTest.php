<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\InitializeCriteria;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class InitializeCriteriaTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var EntityClassResolver */
    private $entityClassResolver;

    /** @var InitializeCriteria */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->entityClassResolver = new EntityClassResolver($this->doctrine);

        $this->processor = new InitializeCriteria(
            $this->doctrineHelper,
            $this->entityClassResolver
        );
    }

    public function testProcessWhenDataAreAlreadyLoaded()
    {
        $this->context->setResult([]);
        $this->processor->process($this->context);

        self::assertNull($this->context->getCriteria());
    }

    public function testProcessWhenCriteriaIsAlreadyInitialized()
    {
        $criteria = new Criteria($this->entityClassResolver);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        self::assertSame($criteria, $this->context->getCriteria());
    }

    public function testProcessForNotManageableEntity()
    {
        $entityClass = 'Test\Class';
        $this->notManageableClassNames = [$entityClass];

        $this->context->setClassName($entityClass);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        self::assertNull($this->context->getCriteria());
    }

    public function testProcessForManageableEntity()
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

    public function testProcessForResourceBasedOnManageableEntity()
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

    public function testProcessForResourceBasedOnNotManageableEntity()
    {
        $entityClass = 'Test\Class';
        $parentResourceClass = 'Test\ParentClass';
        $this->notManageableClassNames = [$entityClass, $parentResourceClass];

        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentResourceClass);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertNull($this->context->getCriteria());
    }
}
