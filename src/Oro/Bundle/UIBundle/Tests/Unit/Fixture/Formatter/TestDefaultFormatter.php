<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Fixture\Formatter;

use Oro\Bundle\UIBundle\Formatter\FormatterInterface;

class TestDefaultFormatter implements FormatterInterface
{
    #[\Override]
    public function format($value, array $formatterArguments = [])
    {
        return sprintf(
            'value:%s,arguments:%s',
            $value,
            implode(',', $formatterArguments)
        );
    }

    #[\Override]
    public function getDefaultValue()
    {
        return 'test_default_value';
    }
}
