<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter;

use Oro\Bundle\ImportExportBundle\Converter\DefaultDataConverter;

class DefaultDataConverterTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var DefaultDataConverter
     */
    protected $dataConverter;

    protected function setUp()
    {
        $this->dataConverter = new DefaultDataConverter();
    }

    /**
     * @param array $importedRecord
     * @param array $exportedRecord
     * @dataProvider convertDataProvider
     */
    public function testConvertImportExport(array $importedRecord, array $exportedRecord)
    {
        $this->assertEquals($exportedRecord, $this->dataConverter->convertToExportFormat($importedRecord));
        $this->assertEquals($importedRecord, $this->dataConverter->convertToImportFormat($exportedRecord));
    }

    /**
     * @return array
     */
    public function convertDataProvider()
    {
        return array(
            'no data' => array(
                'importedRecord' => array(),
                'exportedRecord' => array(),
            ),
            'plain data' => array(
                'importedRecord' => array(
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                ),
                'exportedRecord' => array(
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                ),
            ),
            'complex data' => array(
                'importedRecord' => array(
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                    'emails' => array(
                        'john@qwerty.com',
                        'doe@qwerty.com',
                    ),
                    'addresses' => array(
                        array(
                            'street'     => 'First Street',
                            'postalCode' => '12345',
                        ),
                        array(
                            'street'     => 'Second Street',
                            'street2'    => '2nd',
                            'postalCode' => '98765',
                        ),
                    ),
                ),
                'exportedRecord' => array(
                    'firstName'              => 'John',
                    'lastName'               => 'Doe',
                    'emails:0'               => 'john@qwerty.com',
                    'emails:1'               => 'doe@qwerty.com',
                    'addresses:0:street'     => 'First Street',
                    'addresses:0:postalCode' => '12345',
                    'addresses:1:street'     => 'Second Street',
                    'addresses:1:street2'    => '2nd',
                    'addresses:1:postalCode' => '98765',
                ),
            ),
        );
    }

    public function testConvertToExportFormatTypeCasting()
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

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Delimiter ":" is not allowed in keys
     */
    public function testConvertToExportFormatIncorrectKey()
    {
        $invalidImportedRecord = array(
            'owner:firstName' => 'John'
        );

        $this->dataConverter->convertToExportFormat($invalidImportedRecord);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Can't set nested value under key "owner"
     */
    public function testConvertToImportIncorrectKey()
    {
        $invalidExportedRecord = array(
            'owner'           => 'John Doe',
            'owner:firstName' => 'John',
        );

        $this->dataConverter->convertToImportFormat($invalidExportedRecord);
    }
}
