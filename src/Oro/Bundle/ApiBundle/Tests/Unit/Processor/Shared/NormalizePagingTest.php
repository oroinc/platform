<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizePaging;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class NormalizePagingTest extends GetListProcessorTestCase
{
    /** @var NormalizePaging */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new NormalizePaging();
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $this->processor->process($this->context);
        $this->assertEquals($context, $this->context);
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertNull($this->context->getCriteria());
    }

    public function testProcessOnDisabledPaging()
    {
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $criteria = new Criteria($resolver);
        $criteria->setFirstResult(12);
        $criteria->setMaxResults(-1);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        $this->assertNull($criteria->getMaxResults());
        $this->assertNull($criteria->getFirstResult());
    }

    public function testProcess()
    {
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $criteria = new Criteria($resolver);
        $criteria->setFirstResult(2);
        $criteria->setMaxResults(10);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        $this->assertEquals(10, $criteria->getMaxResults());
        $this->assertEquals(2, $criteria->getFirstResult());
    }
}
