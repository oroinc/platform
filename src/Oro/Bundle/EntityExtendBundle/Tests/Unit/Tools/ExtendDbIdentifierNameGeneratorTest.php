<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class ExtendDbIdentifierNameGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    public function setUp()
    {
        $this->nameGenerator = new ExtendDbIdentifierNameGenerator();
    }

    /**
     * @dataProvider generateEnumTableNameProvider
     */
    public function testGenerateEnumTableName($enumCode, $allowHash, $expected)
    {
        $tableName = $this->nameGenerator->generateEnumTableName($enumCode, $allowHash);
        $this->assertEquals($expected, $tableName);
        $this->assertLessThanOrEqual($this->nameGenerator->getMaxIdentifierSize(), strlen($tableName));
    }

    public function generateEnumTableNameProvider()
    {
        $prefix = ExtendDbIdentifierNameGenerator::ENUM_TABLE_PREFIX;

        return [
            ['test', false, $prefix . 'test'],
            ['test_123', false, $prefix . 'test_123'],
            ['test_5678901234567890', false, $prefix . 'test_5678901234567890'],
            ['test_567890123456789012345', true, $prefix . '6ccda6fa_456789012345'],
            ['acme_customer_status_f1145bcc', true, $prefix . '703594ff_f1145bcc'],
            ['acme_synchronization_direction', true, $prefix . '8575c282_direction'],
            ['acme_synchronization_status', true, $prefix . '2518c27d_status'],
            ['acme_synchronization_status1234567', true, $prefix . '46e0e484_tatus1234567'],
            ['acme_synchronization_key', true, $prefix . '54b8a71a_key'],
            ['acme_synchronization_some_status1', true, $prefix . 'ad2cd284_some_status1'],
            ['acme_synchronization_some_status12', true, $prefix . 'f0add5e6_status12'],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The enum code length must be less or equal 21 characters. Code: test_56789012345678901
     */
    public function testGenerateEnumTableNameWithTooLongEnumCode()
    {
        $this->nameGenerator->generateEnumTableName('test_56789012345678901');
    }
}
