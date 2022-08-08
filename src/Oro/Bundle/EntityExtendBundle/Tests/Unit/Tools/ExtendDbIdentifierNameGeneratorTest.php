<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class ExtendDbIdentifierNameGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtendDbIdentifierNameGenerator */
    private $nameGenerator;

    protected function setUp(): void
    {
        $this->nameGenerator = new ExtendDbIdentifierNameGenerator();
    }

    /**
     * @dataProvider generateEnumTableNameProvider
     */
    public function testGenerateEnumTableName(string $enumCode, bool $allowHash, string $expected)
    {
        $tableName = $this->nameGenerator->generateEnumTableName($enumCode, $allowHash);
        $this->assertEquals($expected, $tableName);
        $this->assertLessThanOrEqual($this->nameGenerator->getMaxIdentifierSize(), strlen($tableName));
    }

    public function generateEnumTableNameProvider(): array
    {
        $prefix = ExtendDbIdentifierNameGenerator::ENUM_TABLE_PREFIX;

        return [
            ['test', false, $prefix . 'test'],
            ['test_123', false, $prefix . 'test_123'],
            ['test_5678901234567890', false, $prefix . 'test_5678901234567890'],
            ['test_567890123456789012345', true, $prefix . 'test_567890123456789012345'],
            ['acme_customer_status_f1145bcc', true, $prefix . 'acme_customer_status_f1145bcc'],
            ['acme_synchronization_direction', true, $prefix . 'acme_synchronization_direction'],
            ['acme_synchronization_status', true, $prefix . 'acme_synchronization_status'],
            ['acme_synchronization_status1234567', true, $prefix . 'acme_synchronization_status1234567'],
            ['acme_synchronization_key', true, $prefix . 'acme_synchronization_key'],
            ['acme_synchronization_some_status1', true, $prefix . 'acme_synchronization_some_status1'],
            ['acme_synchronization_some_status12', true, $prefix . 'acme_synchronization_some_status12'],
        ];
    }

    public function testGenerateEnumTableNameWithTooLongEnumCode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The enum code length must be less or equal 54 characters.'
            . ' Code: extra_long_enum_entity_table_name_test_5678901'
        );
        $this->nameGenerator->generateEnumTableName('extra_long_enum_entity_table_name_test_5678901234567890');
    }
}
