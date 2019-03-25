<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Fixture\Formatter;

class TestFormatter extends TestDefaultFormatter
{
    public function getDefaultValue()
    {
        return 'test_value';
    }
}
