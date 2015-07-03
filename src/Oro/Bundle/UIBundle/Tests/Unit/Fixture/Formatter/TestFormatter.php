<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Fixture\Formatter;

class TestFormatter extends TestDefaultFormatter
{
    public function getFormatterName()
    {
        return 'test_format_name';
    }

    public function getDefaultValue()
    {
        return 'test_value';
    }

    public function getSupportedTypes()
    {
        return ['string'];
    }

    public function isDefaultFormatter()
    {
        return false;
    }
}
