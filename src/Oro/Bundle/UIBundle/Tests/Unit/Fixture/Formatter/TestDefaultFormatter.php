<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Fixture\Formatter;

use Oro\Bundle\UIBundle\Formatter\FormatterInterface;

class TestDefaultFormatter implements FormatterInterface
{
    public function getFormatterName()
    {
        return 'test_default_format_name';
    }

    public function format($parameter, array $formatterArguments = [])
    {
        return sprintf(
            'parameter:%s,arguments:%s',
            $parameter,
            implode(',', $formatterArguments)
        );
    }

    public function getDefaultValue()
    {
        return 'test_default_value';
    }

    public function getSupportedTypes()
    {
        return ['string'];
    }

    public function isDefaultFormatter()
    {
        return true;
    }
}
