<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\DeleteList\NormalizePaging;

class NormalizePagingTest extends DeleteListProcessorTestCase
{
    /** @var NormalizePaging */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();
        $this->processor = new NormalizePaging();
    }

    public function testProcessOnExistingQuery()
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $this->processor->process($this->context);
        $this->assertEquals($context, $this->context);
    }

    public function testProcess()
    {
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);
        $this->context->setCriteria($criteria);

        $this->assertNull($criteria->getFirstResult());
        $this->assertNull($criteria->getMaxResults());

        $this->processor->process($this->context);

        $this->assertEquals(1, $criteria->getFirstResult());
        $this->assertEquals(100, $criteria->getMaxResults());
    }
}
