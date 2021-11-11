<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class AbstractTableDataConverterTest extends \PHPUnit\Framework\TestCase
{
    private array $headerConversionRules = [
        'First Name' => 'firstName',
        'Last Name'  => 'lastName',
        'Job Title'  => 'jobTitle',
        'Email'      => 'emails:0',
        'Numeric Email' => [
            AbstractTableDataConverter::FRONTEND_TO_BACKEND => ['Email (\d+)', 'emails:$1'],
            AbstractTableDataConverter::BACKEND_TO_FRONTEND => ['emails:(\d+)', 'Email $1'],
        ],
        'Empty Regexp'   => [], // invalid format, used to test all cases
        'Ignored Regexp' => [   // invalid format, used to test all cases
            AbstractTableDataConverter::FRONTEND_TO_BACKEND => ['key' => 'value'],
            AbstractTableDataConverter::BACKEND_TO_FRONTEND => ['key' => 'value'],
        ],
    ];

    private array $backendHeader = [
        'firstName',
        'lastName',
        'jobTitle',
        'emails:0',
        'emails:1',
        'emails:2',
    ];

    /** @var AbstractTableDataConverter */
    private $dataConverter;

    protected function setUp(): void
    {
        $dataConverter = $this->getMockForAbstractClass(AbstractTableDataConverter::class);
        $dataConverter->expects($this->any())
            ->method('getHeaderConversionRules')
            ->willReturn($this->headerConversionRules);
        $dataConverter->expects($this->any())
            ->method('getBackendHeader')
            ->willReturn($this->backendHeader);

        $this->dataConverter = $dataConverter;
    }

    /**
     * @dataProvider convertToExportDataProvider
     */
    public function testConvertToExportFormat(array $importedRecord, array $result)
    {
        $this->assertEquals($result, $this->dataConverter->convertToExportFormat($importedRecord));
    }

    public function convertToExportDataProvider(): array
    {
        return [
            'no data' => [
                'importedRecord' => [],
                'result' => [
                    'First Name' => '',
                    'Last Name'  => '',
                    'Job Title'  => '',
                    'Email'      => '',
                    'Email 1'    => '',
                    'Email 2'    => '',
                ]
            ],
            'plain data' => [
                'importedRecord' => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe'
                ],
                'result' => [
                    'First Name' => 'John',
                    'Last Name'  => 'Doe',
                    'Job Title'  => '',
                    'Email'      => '',
                    'Email 1'    => '',
                    'Email 2'    => '',
                ]
            ],
            'complex data' => [
                [
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                    'jobTitle'  => 'Engineer',
                    'emails' => [
                        'john@qwerty.com',
                        'doe@qwerty.com',
                        'john.doe@qwerty.com',
                    ],
                ],
                'result' => [
                    'First Name' => 'John',
                    'Last Name'  => 'Doe',
                    'Job Title'  => 'Engineer',
                    'Email'      => 'john@qwerty.com',
                    'Email 1'    => 'doe@qwerty.com',
                    'Email 2'    => 'john.doe@qwerty.com',
                ]
            ]
        ];
    }

    /**
     * @dataProvider convertToImportDataProvider
     */
    public function testConvertToImportFormat(array $exportedRecord, array $result, bool $skipNull = true)
    {
        $this->assertEquals($result, $this->dataConverter->convertToImportFormat($exportedRecord, $skipNull));
    }

    public function convertToImportDataProvider(): array
    {
        return [
            'no data' => [
                'exportedRecord' => [],
                'result'         => [],
            ],
            'plain data skip null values' => [
                'exportedRecord' => [
                    'First Name' => 'John',
                    'Last Name'  => 'Doe',
                    'Job Title'  => '',
                    'Email'      => '',
                ],
                'result' => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                ]
            ],
            'plain data' => [
                'exportedRecord' => [
                    'First Name' => 'John',
                    'Last Name'  => 'Doe',
                    'Job Title'  => '',
                    'Email'      => '',
                ],
                'result' => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                    'jobTitle'  => null,
                    'emails'    => [],
                ],
                'skipNull' => false
            ],
            'complex data' => [
                'exportedRecord' => [
                    'First Name' => 'John',
                    'Last Name'  => 'Doe',
                    'Job Title'  => 'Engineer',
                    'Email'      => 'john@qwerty.com',
                    'Email 1'    => 'doe@qwerty.com',
                    'Email 2'    => 'john.doe@qwerty.com',
                ],
                'result' => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                    'jobTitle'  => 'Engineer',
                    'emails' => [
                        'john@qwerty.com',
                        'doe@qwerty.com',
                        'john.doe@qwerty.com',
                    ],
                ]
            ],
            'multi-level array data'           => [
                'exportedRecord' => [
                    'First Name'                    => 'John',
                    'Last Name'                     => 'Doe',
                    'Job Title'                     => 'Engineer',
                    'address:0:name'                => 'John',
                    'address:0:last'                => 'Doe',
                    'address:0:city'                => 'City',
                    'address:0:organization:0:name' => 'Main',
                    'address:0:organization:1:name' => 'Default',
                    'address:0:organization:2:name' => '',
                    'address:1'                     => '',
                ],
                'result'         => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                    'jobTitle'  => 'Engineer',
                    'address'   => [
                        [
                            'name'         => 'John',
                            'last'         => 'Doe',
                            'city'         => 'City',
                            'organization' => [
                                ['name' => 'Main'],
                                ['name' => 'Default'],
                            ],
                        ],
                    ],
                ],
            ],
            'multi-level data with empty data' => [
                'exportedRecord' => [
                    'First Name' => 'John',
                    'Last Name'  => 'Doe',
                    'Job Title'  => 'Engineer',
                    'address:0'  => [],
                    'address:1'  => [],
                ],
                'result'         => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                    'jobTitle'  => 'Engineer',
                    'address'   => [],
                ],
            ],
            'no conversion rules' => [
                'exportedRecord' => [
                    'First Name' => 'John',
                    'Last Name'  => 'Doe',
                    'phones:0'   => '12345',
                    'phones:1'   => '98765',
                ],
                'result' => [
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                    'phones' => [
                        '12345',
                        '98765'
                    ]
                ]
            ],
        ];
    }

    public function testConvertToExportFormatExtraFields()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Backend header doesn't contain fields: fax");

        $importedRecordWithExtraData = [
            'firstName' => 'John',
            'lastName'  => 'Doe',
            'fax'       => '5555', // this field is not defined in backend header
        ];

        $this->dataConverter->convertToExportFormat($importedRecordWithExtraData);
    }
}
