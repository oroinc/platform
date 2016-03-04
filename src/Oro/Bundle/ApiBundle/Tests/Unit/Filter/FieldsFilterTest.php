<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Request\DataType;

class FieldsFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateExpression()
    {
        $filter = new FieldsFilter(DataType::STRING);

        $this->assertNull($filter->createExpression(null));
        $this->assertNull($filter->createExpression(new FilterValue('path', 'value', 'operator')));
    }
}
