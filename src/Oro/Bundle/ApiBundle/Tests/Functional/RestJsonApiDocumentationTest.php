<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Nelmio\ApiDocBundle\Formatter\FormatterInterface;

use Symfony\Component\Routing\Route;

use Oro\Bundle\ApiBundle\ApiDoc\CachingApiDocExtractor;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestAllDataTypes;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

class RestJsonApiDocumentationTest extends RestJsonApiTestCase
{
    const VIEW = 'rest_json_api';

    const RESOURCE_ERROR_COUNT = 3;

    /**
     * @see \Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProvider::$templates
     * @var array
     */
    protected $defaultDocumentation = [
        ApiActions::GET                 => 'Get an entity',
        ApiActions::GET_LIST            => 'Get a list of entities',
        ApiActions::DELETE              => 'Delete an entity',
        ApiActions::DELETE_LIST         => 'Delete a list of entities',
        ApiActions::CREATE              => 'Create an entity',
        ApiActions::UPDATE              => 'Update an entity',
        ApiActions::GET_SUBRESOURCE     => [
            'Get a related entity',
            'Get a list of related entities'
        ],
        ApiActions::GET_RELATIONSHIP    => 'Get the relationship data',
        ApiActions::DELETE_RELATIONSHIP => 'Delete the specified members from the relationship',
        ApiActions::ADD_RELATIONSHIP    => 'Add the specified members to the relationship',
        ApiActions::UPDATE_RELATIONSHIP => [
            'Update the relationship',
            'Completely replace every member of the relationship'
        ],
    ];

    /**
     * This test method is used to avoid unnecessary warming up of documentation cache in all other test methods.
     */
    public function testWarmUpCache()
    {
        $apiDocExtractor = $this->getExtractor();
        if ($apiDocExtractor instanceof CachingApiDocExtractor) {
            $apiDocExtractor->warmUp(self::VIEW);
        }
    }

    /**
     * @depends testWarmUpCache
     */
    public function testDocumentation()
    {
        $missingDocs = [];
        $docs = $this->getExtractor()->all(self::VIEW);
        foreach ($docs as $doc) {
            /** @var ApiDoc $annotation */
            $annotation = $doc['annotation'];
            $definition = $annotation->toArray();
            $route = $annotation->getRoute();

            $entityType = $route->getDefault('entity');
            $action = $route->getDefault('_action');
            $association = $route->getDefault('association');
            if ($entityType && $action) {
                $entityClass = $this->getEntityClass($entityType);
                if ($entityClass && !$this->isSkippedEntity($entityClass, $entityType)) {
                    $resourceMissingDocs = $this->checkApiResource($definition, $entityClass, $action, $association);
                    if (!empty($resourceMissingDocs)) {
                        $resource = sprintf('%s %s', $definition['method'], $definition['uri']);
                        $missingDocs[$entityClass][$resource] = $resourceMissingDocs;
                    }
                }
            }
        }

        if (!empty($missingDocs)) {
            self::fail($this->buildFailMessage($missingDocs));
        }
    }

    /**
     * @depends testWarmUpCache
     */
    public function testSimpleDataTypesInRequestAndResponse()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $docs = $this->filterDocs(
            $this->getExtractor()->all(self::VIEW),
            function (Route $route) use ($entityType) {
                return
                    $route->getDefault('entity') === $entityType
                    && $route->getDefault('_action') === ApiActions::CREATE;
            }
        );

        $data = $this->getSimpleFormatter()->format($docs);
        $resourceData = reset($data);
        $resourceData = reset($resourceData);
        $expectedData = $this->loadYamlData('simple_data_types.yml', 'documentation');
        self::assertArrayContains($expectedData, $resourceData);
    }

    /**
     * @depends testWarmUpCache
     */
    public function testSimpleDataTypesInFilters()
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $docs = $this->filterDocs(
            $this->getExtractor()->all(self::VIEW),
            function (Route $route) use ($entityType) {
                return
                    $route->getDefault('entity') === $entityType
                    && $route->getDefault('_action') === ApiActions::GET_LIST;
            }
        );

        $data = $this->getSimpleFormatter()->format($docs);
        $resourceData = reset($data);
        $resourceData = reset($resourceData);
        $expectedData = $this->loadYamlData('simple_data_types_filters.yml', 'documentation');
        self::assertArrayContains($expectedData, $resourceData);
    }

    /**
     * @param string $entityClass
     * @param string $entityType
     *
     * @return bool
     */
    protected function isSkippedEntity($entityClass, $entityType)
    {
        // @todo: remove this variable after https://magecore.atlassian.net/browse/BB-9312 fix
        $temporarySkipEntities = [
            'Oro\Bundle\CustomerBundle\Entity\CustomerGroup',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUserRole',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            'Oro\Bundle\CustomerBundle\Entity\Customer',
            'Extend\Entity\EV_Acc_Internal_Rating',
            'Oro\Bundle\InventoryBundle\Entity\InventoryLevel',
            'Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity',
            'Extend\Entity\EV_Ma_Type',
            'Oro\Bundle\OrderBundle\Entity\OrderAddress',
            'Oro\Bundle\OrderBundle\Entity\OrderDiscount',
            'Oro\Bundle\OrderBundle\Entity\OrderLineItem',
            'Oro\Bundle\OrderBundle\Entity\Order',
            'Oro\Bundle\OrderBundle\Entity\OrderShippingTracking',
            'Extend\Entity\EV_Quote_Customer_Status',
            'Extend\Entity\EV_Quote_Internal_Status',
            'Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision',
            'Oro\Bundle\ProductBundle\Entity\ProductUnit',
            'Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct',
            'Extend\Entity\EV_Prod_Inventory_Status',
            'Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote',
            'Oro\Bundle\RFPBundle\Entity\RequestProductItem',
            'Oro\Bundle\RFPBundle\Entity\RequestProduct',
            'Oro\Bundle\RFPBundle\Entity\Request',
            'Extend\Entity\EV_Rfp_Customer_Status',
            'Extend\Entity\EV_Rfp_Internal_Status',
            'Oro\Bundle\WarehouseBundle\Entity\Warehouse',
            'Oro\Bundle\WebsiteBundle\Entity\Website',
            'Extend\Entity\EV_Variant_Field_Code',
            'Oro\Bundle\ProductBundle\Entity\Brand',
            'Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily',
            'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
            'Oro\Bundle\LocaleBundle\Entity\Localization',
            'Oro\Bundle\TaxBundle\Entity\ProductTaxCode',
            'Oro\Bundle\CatalogBundle\Entity\Category',
            'Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList',
        ];

        return
            in_array($entityClass, $temporarySkipEntities, true)
            || is_a($entityClass, TestFrameworkEntityInterface::class, true)
            || 0 === strpos($entityType, 'testapi')
            || (// any entity from "Extend\Entity" namespace, except enums
                0 === strpos($entityClass, ExtendHelper::ENTITY_NAMESPACE)
                && 0 !== strpos($entityClass, ExtendHelper::ENTITY_NAMESPACE . 'EV_')
            );
    }

    /**
     * @param array  $definition
     * @param string $entityClass
     * @param string $action
     * @param string $association
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function checkApiResource(array $definition, $entityClass, $action, $association)
    {
        $missingDocs = [];
        if (empty($definition['description'])) {
            $missingDocs[] = 'Empty description';
        }
        if (empty($definition['documentation'])) {
            $missingDocs[] = 'Empty documentation';
        } elseif (isset($this->defaultDocumentation[$action])
            && in_array($definition['documentation'], (array)$this->defaultDocumentation[$action], true)
            && 'data_channel' !== $association // @todo: remove this after CRM-8214 fix
        ) {
            $missingDocs[] = sprintf(
                'Missing documentation. Default value is used: "%s"',
                $definition['documentation']
            );
        }
        if (!empty($definition['parameters'])) {
            foreach ($definition['parameters'] as $name => $item) {
                if (empty($item['description'])
                    && 'data_channel' !== $name // @todo: remove this after CRM-8214 fix
                ) {
                    $missingDocs[] = sprintf('Input Field: %s. Empty description.', $name);
                }
            }
        }
        if (!empty($definition['filters'])) {
            foreach ($definition['filters'] as $name => $item) {
                if (empty($item['description'])) {
                    $missingDocs[] = sprintf('Filter: %s. Empty description.', $name);
                }
            }
        }
        if (!$association && !empty($definition['response'])) {
            foreach ($definition['response'] as $name => $item) {
                if (empty($item['description'])
                    && 'data_channel' !== $name // @todo: remove this after CRM-8214 fix
                ) {
                    $missingDocs[] = sprintf('Output Field: %s. Empty description.', $name);
                }
            }
        }

        return $missingDocs;
    }

    /**
     * @param array $missingDocs
     *
     * @return string
     */
    protected function buildFailMessage(array $missingDocs)
    {
        $message = sprintf(
            'Missing documentation for %s entit%s.' . PHP_EOL . PHP_EOL,
            count($missingDocs),
            count($missingDocs) > 1 ? 'ies' : 'y'
        );
        foreach ($missingDocs as $entityClass => $resources) {
            $message .= sprintf('%s' . PHP_EOL, $entityClass);
            foreach ($resources as $resource => $errors) {
                $message .= sprintf('    %s' . PHP_EOL, $resource);
                $i = 0;
                $errorCount = count($errors);
                foreach ($errors as $error) {
                    $message .= sprintf('        %s' . PHP_EOL, $error);
                    $i++;
                    if (self::RESOURCE_ERROR_COUNT === $i && $errorCount > self::RESOURCE_ERROR_COUNT + 2) {
                        $message .= sprintf('        and others %d errors ...' . PHP_EOL, $errorCount - $i);
                        break;
                    }
                }
            }
        }

        return $message;
    }

    /**
     * @param array    $docs
     * @param callable $filter function (Route $route) : bool
     *
     * @return array
     */
    protected function filterDocs(array $docs, $filter)
    {
        $result = [];
        foreach ($docs as $doc) {
            /** @var ApiDoc $annotation */
            $annotation = $doc['annotation'];
            if ($filter($annotation->getRoute())) {
                $result[] = $doc;
            }
        }

        return $result;
    }

    /**
     * @return ApiDocExtractor
     */
    protected function getExtractor()
    {
        return self::getContainer()->get('nelmio_api_doc.extractor.api_doc_extractor');
    }

    /**
     * @return FormatterInterface
     */
    protected function getSimpleFormatter()
    {
        return self::getContainer()->get('nelmio_api_doc.formatter.simple_formatter');
    }
}
