<?php

namespace Oro\Component\Config\Tests\Unit\Fixtures;

class TestService
{
    public static function func1()
    {
        return 'func1';
    }

    public static function func2($val)
    {
        return 'func2 + ' . ((null === $val) ? 'NULL' : $val);
    }

    public static function func3($val1, $val2)
    {
        return 'func3 + ' . ((null === $val1) ? 'NULL' : $val1) . ' + ' . ((null === $val2) ? 'NULL' : $val2);
    }

    public static function func4($val1, $val2, $val3)
    {
        return 'func4 + ' . ((null === $val1) ? 'NULL' : $val1) . ' + ' . ((null === $val2) ? 'NULL' : $val2) . ' + ' .
            ((null === $val3) ? 'NULL' : $val3);
    }
}
