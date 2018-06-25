<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\EntityExtendBundle\DBAL\Types\HTMLEscapedType;

class HTMLEscapedTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var HTMLEscapedType */
    protected $type;

    /** @var AbstractPlatform */
    protected $platform;

    protected function setUp()
    {
        $this->type = $this
            ->getMockBuilder(
                'Oro\Bundle\EntityExtendBundle\DBAL\Types\HTMLEscapedType'
            )
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platform = $this
            ->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * @param mixed $value
     * @param mixed $expected
     *
     * @dataProvider convertToDatabaseValueDataProvider
     */
    public function testConvertToDatabaseValue($value, $expected)
    {
        $this->assertSame(
            $expected,
            $this->type->convertToDatabaseValue($value, $this->platform)
        );
    }

    /**
     * @param mixed $value
     * @param mixed $expected
     *
     * @dataProvider convertToDatabaseValueDataProvider
     */
    public function testConvertToPHPValue($value, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->type->convertToPHPValue($value, $this->platform)
        );
    }

    /** @return array */
    public function convertToDatabaseValueDataProvider()
    {
        $html = <<<EOF
<iframe src="https://www.somehost"></iframe><script>alert('Some script')</script><style type="text/css">
   h1 { 
    font-size: 120%; 
   }
</style><link rel="stylesheet" href="mystylesheet.css" onload="sheetLoaded()" onerror="sheetError()">
EOF;

        return [
            'null' => [null, null],
            'empty' => ['', ''],
            'string' => [$html, $html],
        ];
    }
}
