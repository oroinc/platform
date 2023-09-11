<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDocumentation;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestAllDataTypes;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestUniqueKeyIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestResourceWithoutIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DocumentationTest extends RestJsonApiTestCase
{
    use DocumentationTestTrait;

    /** @var string used in DocumentationTestTrait */
    private const VIEW = 'rest_json_api';

    private function isSkippedField(string $entityClass, string $fieldName): bool
    {
        return
            // remove this after CRM-8214 fix
            'data_channel' === $fieldName;
    }

    /**
     * This test method is used to avoid unnecessary warming up of documentation cache in all other test methods.
     */
    public function testWarmUpCache()
    {
        $this->warmUpDocumentationCache();
    }

    /**
     * @depends testWarmUpCache
     */
    public function testDocumentation()
    {
        $this->checkDocumentation();
    }

    /**
     * @depends testWarmUpCache
     */
    public function testSimpleDataTypesInRequestAndResponse()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiAction::CREATE);

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        $expectedData = $this->loadYamlData('simple_data_types.yml', 'documentation');
        self::assertArrayContainsAndSectionKeysEqual($expectedData, $resourceData);
    }

    /**
     * @depends testWarmUpCache
     */
    public function testSubresourceWithEntityIdentifierTargetTypeShouldBeInRightCategory()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $docs = $this->getSubresourceEntityDocsForAction(
            $entityType,
            'entity-identifier-target',
            ApiAction::GET_SUBRESOURCE
        );

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals($resourceData['section'], $entityType);
    }

    /**
     * @depends testWarmUpCache
     */
    public function testSubresourceWithUnknownTargetTypeShouldBeExcluded()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $docs = $this->getSubresourceEntityDocsForAction(
            $entityType,
            'unregistered-target',
            ApiAction::GET_SUBRESOURCE
        );

        self::assertEmpty($docs);
    }

    /**
     * @depends testWarmUpCache
     */
    public function testSimpleDataTypesInFilters()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiAction::GET_LIST);

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        $expectedData = $this->loadYamlData('simple_data_types_filters.yml', 'documentation');
        self::assertArrayContainsAndSectionKeysEqual($expectedData, $resourceData);
    }

    /**
     * @depends testWarmUpCache
     */
    public function testAssociationsInRequestAndResponse()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiAction::CREATE);

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        $expectedData = $this->loadYamlData('associations.yml', 'documentation');
        self::assertArrayContainsAndSectionKeysEqual($expectedData, $resourceData);
    }

    /**
     * @depends testWarmUpCache
     */
    public function testFilters()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiAction::GET_LIST);

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        $expectedData = $this->loadYamlData('filters.yml', 'documentation');
        self::assertArrayContainsAndSectionKeysEqual($expectedData, $resourceData);
    }

    /**
     * @depends testWarmUpCache
     */
    public function testResourceWithoutIdentifier()
    {
        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiAction::CREATE);

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertArrayContains(
            [
                'description'   => 'Create Resource Without Identifier',
                'documentation' => 'Create resource without identifier',
                'parameters'    => [
                    'name' => [
                        'dataType'    => 'string',
                        'description' => 'The name of a resource'
                    ]
                ],
                'response'      => [
                    'name' => [
                        'dataType'    => 'string',
                        'description' => 'The name of a resource'
                    ]
                ]
            ],
            $resourceData
        );
    }

    /**
     * @depends testWarmUpCache
     * @dataProvider resourceWithCustomDescriptionOfIdentifierDataProvider
     */
    public function testResourceWithCustomDescriptionOfIdentifier(string $action, array $expectedData)
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);
        $docs = $this->getEntityDocsForAction($entityType, $action);

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertArrayContains($expectedData, $resourceData);
    }

    public static function resourceWithCustomDescriptionOfIdentifierDataProvider(): array
    {
        return [
            [
                ApiAction::GET,
                [
                    'response'     => [
                        'id' => [
                            'description' => 'The unique identifier of a resource. It is a key.'
                        ]
                    ],
                    'requirements' => [
                        'id' => [
                            'description' => 'The unique identifier of a resource. It is a key.'
                        ]
                    ]
                ]
            ],
            [
                ApiAction::GET_LIST,
                [
                    'response'   => [
                        'id' => [
                            'description' => 'The unique identifier of a resource. It is a key.'
                        ]
                    ]
                ]
            ],
            [
                ApiAction::CREATE,
                [
                    'parameters' => [
                        'id' => [
                            'description' => 'The unique identifier of a resource. It is a key.'
                        ]
                    ],
                    'response'   => [
                        'id' => [
                            'description' => 'The unique identifier of a resource. It is a key.'
                        ]
                    ]
                ]
            ],
            [
                ApiAction::UPDATE,
                [
                    'parameters'   => [
                        'id' => [
                            'description' => '<p>The unique identifier of a resource. It is a key.</p>'
                                . '<p><strong>The required field.</strong></p>'
                        ]
                    ],
                    'response'     => [
                        'id' => [
                            'description' => 'The unique identifier of a resource. It is a key.'
                        ]
                    ],
                    'requirements' => [
                        'id' => [
                            'description' => 'The unique identifier of a resource. It is a key.'
                        ]
                    ]
                ]
            ],
            [
                ApiAction::DELETE,
                [
                    'requirements' => [
                        'id' => [
                            'description' => 'The unique identifier of a resource. It is a key.'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @depends testWarmUpCache
     */
    public function testOptionsDocumentation()
    {
        $this->checkOptionsDocumentationForEntity($this->getEntityType(User::class));
    }

    /**
     * @depends testWarmUpCache
     */
    public function testUpsertNoteForResourceThatSupportsUpsertOperationById()
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiAction::CREATE);

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertStringContainsString(
            '<p><strong>Note:</strong>'
            . ' This resource supports '
            . '<a href="https://doc.oroinc.com/api/upsert-operation/" target="_blank">the upsert operation</a>'
            . ' by the resource identifier.</p>',
            $resourceData['documentation']
        );
    }

    /**
     * @depends testWarmUpCache
     */
    public function testUpsertNoteForResourceThatSupportsUpsertOperationByGroupsOfFields()
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiAction::CREATE);

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertStringContainsString(
            '<p><strong>Note:</strong>'
            . ' This resource supports '
            . '<a href="https://doc.oroinc.com/api/upsert-operation/" target="_blank">the upsert operation</a>'
            . ' by the following groups of fields:</p>'
            . "\n<ul>"
            . "\n  <li>\"key7\"</li>"
            . "\n  <li>\"key6\", \"parent\"</li>"
            . "\n  <li>\"children\", \"key6\"</li>"
            . "\n  <li>\"key3\"</li>"
            . "\n  <li>\"key4\"</li>"
            . "\n  <li>\"key1\", \"key2\"</li>"
            . "\n</ul>",
            $resourceData['documentation']
        );
    }

    /**
     * @depends testWarmUpCache
     * This test should be at the end to avoid unnecessary warming up documentation cache in previous tests.
     */
    public function testFiltersWhenRequestingSomeMetaPropertiesIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['disable_meta_properties' => ['title']],
            true
        );
        $this->warmUpDocumentationCache();

        $entityType = $this->getEntityType(TestDepartment::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiAction::GET_LIST);

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        $expectedData = $this->loadYamlData('filters.yml', 'documentation');
        self::assertArrayContainsAndSectionKeysEqual($expectedData, $resourceData);
    }

    /**
     * @depends testWarmUpCache
     * This test should be at the end to avoid unnecessary warming up documentation cache in previous tests.
     */
    public function testFiltersWhenMetaAndIncludeAndFieldsFiltersAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'disable_meta_properties' => true,
                'disable_inclusion'       => true,
                'disable_fieldset'        => true
            ],
            true
        );
        $this->warmUpDocumentationCache();

        $entityType = $this->getEntityType(TestDepartment::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiAction::GET_LIST);

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        $expectedData = $this->loadYamlData('filters.yml', 'documentation');
        unset(
            $expectedData['filters']['meta'],
            $expectedData['filters']['include']
        );
        foreach ($expectedData['filters'] as $key => $val) {
            if (str_starts_with($key, 'fields[')) {
                unset($expectedData['filters'][$key]);
            }
        }
        self::assertArrayContainsAndSectionKeysEqual($expectedData, $resourceData);
    }
}
