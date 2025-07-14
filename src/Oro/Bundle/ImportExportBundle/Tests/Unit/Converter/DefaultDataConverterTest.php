<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter;

use Oro\Bundle\ImportExportBundle\Converter\DefaultDataConverter;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use PHPUnit\Framework\TestCase;

class DefaultDataConverterTest extends TestCase
{
    private DefaultDataConverter $dataConverter;

    #[\Override]
    protected function setUp(): void
    {
        $this->dataConverter = new DefaultDataConverter();
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvertImportExport(array $importedRecord, array $exportedRecord): void
    {
        $this->assertEquals($exportedRecord, $this->dataConverter->convertToExportFormat($importedRecord));
        $this->assertEquals($importedRecord, $this->dataConverter->convertToImportFormat($exportedRecord));
    }

    public function convertDataProvider(): array
    {
        return [
            'no data' => [
                'importedRecord' => [],
                'exportedRecord' => [],
            ],
            'plain data' => [
                'importedRecord' => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                ],
                'exportedRecord' => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                ],
            ],
            'complex data' => [
                'importedRecord' => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                    'emails' => [
                        'john@qwerty.com',
                        'doe@qwerty.com',
                    ],
                    'addresses' => [
                        [
                            'street'     => 'First Street',
                            'postalCode' => '12345',
                        ],
                        [
                            'street'     => 'Second Street',
                            'street2'    => '2nd',
                            'postalCode' => '98765',
                        ],
                    ],
                ],
                'exportedRecord' => [
                    'firstName'              => 'John',
                    'lastName'               => 'Doe',
                    'emails:0'               => 'john@qwerty.com',
                    'emails:1'               => 'doe@qwerty.com',
                    'addresses:0:street'     => 'First Street',
                    'addresses:0:postalCode' => '12345',
                    'addresses:1:street'     => 'Second Street',
                    'addresses:1:street2'    => '2nd',
                    'addresses:1:postalCode' => '98765',
                ],
            ],
        ];
    }

    public function testConvertToExportFormatTypeCasting(): void
    {
        $testData = [
            'intAttribute' => 123,
            'floatAttribute' => 123.12345,
            'boolAttribute' => true,
            'intZeroAttribute' => 0,
            'floatZeroAttribute' => 0.0,
            'boolZeroAttribute' => false,
            'stringZeroAttribute' => '0',
        ];
        $expectedResult = [
            'intAttribute' => '123',
            'floatAttribute' => '123.12345',
            'boolAttribute' => '1',
            'intZeroAttribute' => '0',
            'floatZeroAttribute' => '0',
            'boolZeroAttribute' => '0',
            'stringZeroAttribute' => '0',
        ];

        $this->assertEquals($expectedResult, $this->dataConverter->convertToExportFormat($testData));
    }

    public function testConvertToExportFormatIncorrectKey(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Delimiter ":" is not allowed in keys');

        $invalidImportedRecord = [
            'owner:firstName' => 'John'
        ];

        $this->dataConverter->convertToExportFormat($invalidImportedRecord);
    }

    public function testConvertToImportIncorrectKey(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can\'t set nested value under key "owner"');

        $invalidExportedRecord = [
            'owner'           => 'John Doe',
            'owner:firstName' => 'John',
        ];

        $this->dataConverter->convertToImportFormat($invalidExportedRecord);
    }
}
