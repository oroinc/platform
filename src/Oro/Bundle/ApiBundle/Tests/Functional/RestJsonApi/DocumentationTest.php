<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestAllDataTypes;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestResourceWithoutIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\UserBundle\Entity\User;

class DocumentationTest extends RestJsonApiTestCase
{
    use DocumentationTestTrait;

    /** @var string used in DocumentationTestTrait */
    private const VIEW = 'rest_json_api';

    /**
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return bool
     */
    private function isSkippedField($entityClass, $fieldName)
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
        $docs = $this->getEntityDocsForAction($entityType, ApiActions::CREATE);

        $data = $this->getSimpleFormatter()->format($docs);
        $resourceData = reset($data);
        $resourceData = reset($resourceData);
        $expectedData = $this->loadYamlData('simple_data_types.yml', 'documentation');
        self::assertArrayContains($expectedData, $resourceData);
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
            ApiActions::GET_SUBRESOURCE
        );

        $data = $this->getSimpleFormatter()->format($docs);
        $resourceData = reset($data);
        $resourceData = reset($resourceData);
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
            ApiActions::GET_SUBRESOURCE
        );

        self::assertEmpty($docs);
    }

    /**
     * @depends testWarmUpCache
     */
    public function testSimpleDataTypesInFilters()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiActions::GET_LIST);

        $data = $this->getSimpleFormatter()->format($docs);
        $resourceData = reset($data);
        $resourceData = reset($resourceData);
        $expectedData = $this->loadYamlData('simple_data_types_filters.yml', 'documentation');
        self::assertArrayContains($expectedData, $resourceData);
    }

    /**
     * @depends testWarmUpCache
     */
    public function testAssociationsInRequestAndResponse()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiActions::CREATE);

        $data = $this->getSimpleFormatter()->format($docs);
        $resourceData = reset($data);
        $resourceData = reset($resourceData);
        $expectedData = $this->loadYamlData('associations.yml', 'documentation');
        self::assertArrayContains($expectedData, $resourceData);
    }

    /**
     * @depends testWarmUpCache
     */
    public function testAssociationsInFilters()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiActions::GET_LIST);

        $data = $this->getSimpleFormatter()->format($docs);
        $resourceData = reset($data);
        $resourceData = reset($resourceData);
        $expectedData = $this->loadYamlData('associations_filters.yml', 'documentation');
        self::assertArrayContains($expectedData, $resourceData);
    }

    /**
     * @depends testWarmUpCache
     */
    public function testResourceWithoutIdentifier()
    {
        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $docs = $this->getEntityDocsForAction($entityType, ApiActions::CREATE);

        $data = $this->getSimpleFormatter()->format($docs);
        $resourceData = reset($data);
        $resourceData = reset($resourceData);
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
}
