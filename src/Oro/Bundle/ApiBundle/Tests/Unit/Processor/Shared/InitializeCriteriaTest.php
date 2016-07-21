<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Shared\InitializeCriteria;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class InitializeCriteriaTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var InitializeCriteria */
    protected $processor;

    public function setUp()
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

        $this->assertNull($this->context->getCriteria());
    }

    public function testProcessWhenCriteriaIsAlreadyInitialized()
    {
        $criteria = new Criteria($this->entityClassResolver);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        $this->assertSame($criteria, $this->context->getCriteria());
    }

    public function testProcessForNotManageableEntity()
    {
        $entityClass = 'Test\Class';
        $this->notManageableClassNames = [$entityClass];

        $this->context->setClassName($entityClass);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getCriteria());
    }

    public function testProcessForManageableEntity()
    {
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';

        $this->context->setClassName($entityClass);
        $this->processor->process($this->context);

        $this->assertEquals(
            new Criteria($this->entityClassResolver),
            $this->context->getCriteria()
        );
    }
}
