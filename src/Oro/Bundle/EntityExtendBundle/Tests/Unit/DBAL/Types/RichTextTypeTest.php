<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\EntityExtendBundle\DBAL\Types\RichTextType;

class RichTextTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var RichTextType */
    protected $type;

    /** @var AbstractPlatform */
    protected $platform;

    protected function setUp()
    {
        $this->type = $this
            ->getMockBuilder(
                'Oro\Bundle\EntityExtendBundle\DBAL\Types\RichTextType'
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
</style>
EOF;

        return [
            'null' => [null, null],
            'empty' => ['', ''],
            'string' => [$html, $html],
        ];
    }
}
