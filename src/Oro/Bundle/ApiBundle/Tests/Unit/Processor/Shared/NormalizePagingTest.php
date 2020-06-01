<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizePaging;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class NormalizePagingTest extends GetListProcessorTestCase
{
    /** @var NormalizePaging */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new NormalizePaging();
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $this->processor->process($this->context);
        self::assertEquals($context, $this->context);
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->processor->process($this->context);

        self::assertNull($this->context->getCriteria());
    }

    public function testProcessOnDisabledPaging()
    {
        $criteria = new Criteria();
        $criteria->setFirstResult(12);
        $criteria->setMaxResults(-1);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        self::assertNull($criteria->getMaxResults());
        self::assertNull($criteria->getFirstResult());
    }

    public function testProcess()
    {
        $criteria = new Criteria();
        $criteria->setFirstResult(2);
        $criteria->setMaxResults(10);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        self::assertEquals(10, $criteria->getMaxResults());
        self::assertEquals(2, $criteria->getFirstResult());
    }
}
