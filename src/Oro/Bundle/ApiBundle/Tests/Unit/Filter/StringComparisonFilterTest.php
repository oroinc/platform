<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\StringComparisonFilter;
use PHPUnit\Framework\TestCase;

class StringComparisonFilterTest extends TestCase
{
    public function testGetValueNormalizationOptionsWhenAllowEmptyOptionIsNotSet(): void
    {
        $filter = new StringComparisonFilter('string');
        self::assertSame([], $filter->getValueNormalizationOptions());
    }

    public function testGetValueNormalizationOptionsWhenAllowEmptyOptionIsSetToFalse(): void
    {
        $filter = new StringComparisonFilter('string');
        $filter->setAllowEmpty(false);
        self::assertSame([], $filter->getValueNormalizationOptions());
    }

    public function testGetValueNormalizationOptionsWhenAllowEmptyOptionIsSetToTrue(): void
    {
        $filter = new StringComparisonFilter('string');
        $filter->setAllowEmpty(true);
        self::assertSame(['allow_empty' => true], $filter->getValueNormalizationOptions());
    }
}
