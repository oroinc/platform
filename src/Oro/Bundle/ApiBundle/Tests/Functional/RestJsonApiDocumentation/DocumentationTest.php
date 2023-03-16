<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDocumentation;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestAllDataTypes;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
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
     */
    public function testOptionsDocumentation()
    {
        $this->checkOptionsDocumentationForEntity($this->getEntityType(User::class));
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
