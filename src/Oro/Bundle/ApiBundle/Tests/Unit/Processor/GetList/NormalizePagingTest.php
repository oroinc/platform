<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\GetList\NormalizePaging;

class NormalizePagingTest extends GetListProcessorTestCase
{
    /** @var NormalizePaging */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $classResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setCriteria(new Criteria($classResolver));

        $this->processor = new NormalizePaging();
    }

    public function testProcessOnExistingQuery()
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $this->processor->process($this->context);
        $this->assertEquals($context, $this->context);
    }

    public function testProcessOnDisabledPaging()
    {
        $criteria = $this->context->getCriteria();
        $criteria->setFirstResult(12);
        $criteria->setMaxResults(-1);

        $this->processor->process($this->context);

        $this->assertNull($criteria->getMaxResults());
        $this->assertNull($criteria->getFirstResult());
    }

    public function testProcess()
    {
        $criteria = $this->context->getCriteria();
        $criteria->setFirstResult(2);
        $criteria->setMaxResults(10);

        $this->processor->process($this->context);

        $this->assertEquals(10, $criteria->getMaxResults());
        $this->assertEquals(2, $criteria->getFirstResult());
    }
}
