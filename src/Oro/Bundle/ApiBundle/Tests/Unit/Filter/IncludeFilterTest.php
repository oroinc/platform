<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\IncludeFilter;
use Oro\Bundle\ApiBundle\Request\DataType;

class IncludeFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateExpression()
    {
        $filter = new IncludeFilter(DataType::STRING);

        $this->assertNull($filter->createExpression(null));
        $this->assertNull($filter->createExpression(new FilterValue('path', 'value', 'operator')));
    }
}
