<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\DeleteList\SetDeleteLimit;

class SetDeleteLimitTest extends DeleteListProcessorTestCase
{
    /** @var SetDeleteLimit */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();
        $this->processor = new SetDeleteLimit();
    }

    public function testProcessOnExistingQuery()
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $this->processor->process($this->context);
        $this->assertEquals($context, $this->context);
    }

    public function testProcessOnSettedMaxResult()
    {
        $maxResults = 2;
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);
        $criteria->setMaxResults($maxResults);
        $this->context->setCriteria($criteria);

        $this->processor->process($this->context);

        $this->assertEquals($maxResults, $criteria->getMaxResults());
    }

    public function testProcess()
    {
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);
        $this->context->setCriteria($criteria);

        $this->assertNull($criteria->getMaxResults());

        $this->processor->process($this->context);

        $this->assertEquals(100, $criteria->getMaxResults());
    }
}
