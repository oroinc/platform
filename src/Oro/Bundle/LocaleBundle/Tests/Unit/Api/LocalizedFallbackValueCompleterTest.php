<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Api;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\CompleteDefinition\CompleteDefinitionHelperTestCase;
use Oro\Bundle\LocaleBundle\Api\LocalizedFallbackValueCompleter;

class LocalizedFallbackValueCompleterTest extends CompleteDefinitionHelperTestCase
{
    /** @var LocalizedFallbackValueCompleter */
    private $localizedFallbackValueCompleter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localizedFallbackValueCompleter = new LocalizedFallbackValueCompleter();
    }

    public function testCompleteLocalizedFallbackValue()
    {
        $dataType = 'localizedFallbackValue:names';
        $fieldName = 'name';
        $config = $this->createConfigObject([
            'fields' => [
                $fieldName => [
                    'data_type' => $dataType
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $result = $this->localizedFallbackValueCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $fieldName,
            $config->getField($fieldName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields'                          => [
                    $fieldName => [
                        'data_type'     => 'string',
                        'property_path' => '_',
                        'depends_on'    => ['names']
                    ],
                    'names'    => [
                        'exclude'     => true,
                        'max_results' => -1
                    ]
                ],
                'localized_fallback_value_fields' => [$fieldName]
            ],
            $config
        );
    }

    public function testCompleteLocalizedFallbackValueWhenItsNameEqualToTargetFieldName()
    {
        $fieldName = 'names';
        $dataType = 'localizedFallbackValue:' . $fieldName;
        $config = $this->createConfigObject([
            'fields' => [
                $fieldName       => [
                    'data_type' => $dataType
                ],
                '_' . $fieldName => [
                    'property_path' => $fieldName
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $result = $this->localizedFallbackValueCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $fieldName,
            $config->getField($fieldName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields'                          => [
                    $fieldName       => [
                        'data_type'     => 'string',
                        'property_path' => '_',
                        'depends_on'    => [$fieldName]
                    ],
                    '_' . $fieldName => [
                        'property_path' => $fieldName,
                        'exclude'       => true,
                        'max_results'   => -1
                    ]
                ],
                'localized_fallback_value_fields' => [$fieldName]
            ],
            $config
        );
    }

    public function testCompleteLocalizedFallbackValueWhenItsNameEqualToTargetFieldNameAndTargetFieldWasNotRenamed()
    {
        $fieldName = 'names';
        $dataType = 'localizedFallbackValue:' . $fieldName;
        $config = $this->createConfigObject([
            'fields' => [
                $fieldName => [
                    'data_type' => $dataType
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The circular dependency is detected for localized fallback value field "Test\Class::names".'
            . ' To solve this you can rename the target property of this field.'
            . ' For example:' . "\n"
            . '_names:' . "\n"
            . '    property_path: names'
        );

        $this->localizedFallbackValueCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $fieldName,
            $config->getField($fieldName),
            $dataType,
            $version,
            $requestType
        );
    }

    public function testCompleteLocalizedFallbackValueWhenAnotherLocalizedFallbackValueAlreadyCompleted()
    {
        $dataType = 'localizedFallbackValue:names';
        $fieldName = 'name';
        $config = $this->createConfigObject([
            'fields'                          => [
                'description' => [
                    'data_type'     => 'string',
                    'property_path' => '_',
                    'depends_on'    => ['descriptions']
                ],
                $fieldName    => [
                    'data_type' => $dataType
                ]
            ],
            'localized_fallback_value_fields' => ['another']
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $result = $this->localizedFallbackValueCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $fieldName,
            $config->getField($fieldName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields'                          => [
                    'description' => [
                        'data_type'     => 'string',
                        'property_path' => '_',
                        'depends_on'    => ['descriptions']
                    ],
                    $fieldName    => [
                        'data_type'     => 'string',
                        'property_path' => '_',
                        'depends_on'    => ['names']
                    ],
                    'names'       => [
                        'exclude'     => true,
                        'max_results' => -1
                    ]
                ],
                'localized_fallback_value_fields' => ['another', $fieldName]
            ],
            $config
        );
    }

    public function testNotSupportedDataType()
    {
        $dataType = 'another_type';
        $fieldName = 'name';
        $config = $this->createConfigObject([
            'fields' => [
                $fieldName => [
                    'data_type' => $dataType
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $result = $this->localizedFallbackValueCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $fieldName,
            $config->getField($fieldName),
            $dataType,
            $version,
            $requestType
        );
        self::assertFalse($result);

        $this->assertConfig(
            [
                'fields' => [
                    $fieldName => [
                        'data_type' => $dataType
                    ]
                ]
            ],
            $config
        );
    }
}
