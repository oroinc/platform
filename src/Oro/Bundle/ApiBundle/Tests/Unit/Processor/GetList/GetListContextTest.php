<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;

class GetListContextTest extends \PHPUnit_Framework_TestCase
{
    public function testTotalCountCallback()
    {
        $context = new GetListContext();

        $this->assertNull($context->getTotalCountCallback());

        $totalCountCallback = [$this, 'calculateTotalCount'];

        $context->setTotalCountCallback($totalCountCallback);
        $this->assertEquals($totalCountCallback, $context->getTotalCountCallback());
        $this->assertEquals($totalCountCallback, $context->get(GetListContext::TOTAL_COUNT_CALLBACK));
    }
}
