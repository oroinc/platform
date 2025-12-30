<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\GetList\ApplyInitializeCriteriaCallback;

class ApplyInitializeCriteriaCallbackTest extends GetListProcessorTestCase
{
    private ApplyInitializeCriteriaCallback $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ApplyInitializeCriteriaCallback();
    }

    public function testProcessWhenQueryIsAlreadyBuilt(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $this->context->setQuery($qb);
        $this->context->setInitializeCriteriaCallback(function (Criteria $criteria): void {
            $criteria->andWhere(Criteria::expr()->eq('id', 1));
        });
        $this->processor->process($this->context);

        self::assertSame($qb, $this->context->getQuery());
    }

    public function testProcessWhenCriteriaObjectDoesNotExist(): void
    {
        $this->context->setInitializeCriteriaCallback(function (Criteria $criteria): void {
            $criteria->andWhere(Criteria::expr()->eq('id', 1));
        });
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasQuery());
    }

    public function testProcessWhenNoInitializeCriteriaCallback(): void
    {
        $this->context->setCriteria(new Criteria());
        $this->processor->process($this->context);

        self::assertNull($this->context->getCriteria()->getWhereExpression());
    }

    public function testProcess(): void
    {
        $this->context->setCriteria(new Criteria());
        $this->context->setInitializeCriteriaCallback(function (Criteria $criteria): void {
            $criteria->andWhere(Criteria::expr()->eq('id', 1));
        });
        $this->processor->process($this->context);

        self::assertNotNull($this->context->getCriteria()->getWhereExpression());
    }
}
