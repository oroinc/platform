<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\EntityBundle\DoctrineExtensions\DBAL\Types\DurationType;

class DurationTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var DurationType */
    private $type;

    /** @var AbstractPlatform */
    private $platform;

    protected function setUp(): void
    {
        $this->type = new DurationType();

        $this->platform = $this->createMock(AbstractPlatform::class);
    }

    /**
     * @dataProvider convertToDatabaseValueDataProvider
     */
    public function testConvertToDatabaseValue($value, $expected)
    {
        $this->assertSame(
            $expected,
            $this->type->convertToDatabaseValue($value, $this->platform)
        );
    }

    public function convertToDatabaseValueDataProvider(): array
    {
        return [
            'null' => [null, null],
            'empty' => ['', ''],
            'string' => ['10', '10'],
            'integer' => [10, 10],
        ];
    }

    public function testConvertToPHPValue()
    {
        $this->assertEquals(10, $this->type->convertToPHPValue('10', $this->platform));
    }
}
