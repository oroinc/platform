<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Util;

use Oro\Component\MessageQueue\Util\VarExport;

class VarExportTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithValueAsArgument()
    {
        new VarExport('aVal');
    }

    /**
     * @dataProvider provideValues
     */
    public function testShouldConvertValueToStringUsingVarExportFunction($value, $expected)
    {
        $this->assertSame($expected, (string) new VarExport($value));
    }

    public function provideValues()
    {
        return [
            ['aString', "'aString'"],
            [123, '123'],
            [['foo' => 'fooVal'], "array (\n  'foo' => 'fooVal',\n)"],
        ];
    }
}
