<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\GetList\ValidateSorting;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class ValidateSortingTest extends GetListProcessorOrmRelatedTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var ValidateSorting */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->context->setAction('get_list');

        $this->processor = new ValidateSorting($this->doctrineHelper, $this->configProvider);
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessWhenSortByExcludedFieldRequested()
    {
        $sortersConfig = $this->getSortersConfig(['id']);
        $sortersConfig->getField('id')->setExcluded(true);

        $sorterFilter = new SortFilter('integer');
        $filters      = new FilterCollection();
        $filters->add('sort', $sorterFilter);

        $this->prepareFilters();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->context->set('filters', $filters);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenNoSorters()
    {
        $sortersConfig = $this->getSortersConfig();

        $this->prepareFilters();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByNotAllowedFieldRequested()
    {
        $sortersConfig = $this->getSortersConfig(['name']);
        $sortersConfig->getField('name')->setExcluded(true);

        $this->prepareFilters();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortBySeveralNotAllowedFieldRequested()
    {
        $sortersConfig = $this->getSortersConfig(['name']);
        $sortersConfig->getField('name')->setExcluded(true);

        $this->prepareFilters('id,-label');

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "id, label" fields are not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByAllowedFieldRequested()
    {
        $sortersConfig = $this->getSortersConfig(['id']);

        $this->prepareFilters();

        $this->context->setConfigOfSorters($sortersConfig);
        $this->processor->process($this->context);

        $this->assertEmpty($this->context->getErrors());
    }

    public function testProcessWhenSortByAllowedRenamedFieldRequested()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name1']);
        $primaryEntityConfig->getField('name1')->setPropertyPath('name');
        $primarySortersConfig = $this->getSortersConfig(['name1']);
        $primarySortersConfig->getField('name1')->setPropertyPath('name');

        $this->prepareFilters('name1');

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfSorters($primarySortersConfig);

        $this->processor->process($this->context);

        $this->assertEmpty($this->context->getErrors());
        $this->assertEquals(
            ['name' => 'ASC'],
            $this->context->getFilterValues()->get('sort')->getValue()
        );
    }

    public function testProcessWhenSortByAllowedAssociationFieldRequested()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);
        $categoryConfig = $this->getConfig(['name'], ['name']);

        $this->prepareFilters('category.name');

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $this->getEntityClass('Category'),
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [
                    new EntityDefinitionConfigExtra($this->context->getAction()),
                    new SortersConfigExtra()
                ]
            )
            ->willReturn($categoryConfig);

        $this->processor->process($this->context);

        $this->assertEmpty($this->context->getErrors());
        $this->assertEquals(
            ['category.name' => 'ASC'],
            $this->context->getFilterValues()->get('sort')->getValue()
        );
    }

    public function testProcessWhenSortByAllowedRenamedAssociationRequested()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category1']);
        $primaryEntityConfig->getField('category1')->setPropertyPath('category');
        $categoryConfig = $this->getConfig(['name'], ['name']);

        $this->prepareFilters('category1.name');

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $this->getEntityClass('Category'),
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [
                    new EntityDefinitionConfigExtra($this->context->getAction()),
                    new SortersConfigExtra()
                ]
            )
            ->willReturn($categoryConfig);

        $this->processor->process($this->context);

        $this->assertEmpty($this->context->getErrors());
        $this->assertEquals(
            ['category.name' => 'ASC'],
            $this->context->getFilterValues()->get('sort')->getValue()
        );
    }

    public function testProcessWhenSortByAllowedRenamedAssociationAndRenamedRelatedFieldRequested()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category1']);
        $primaryEntityConfig->getField('category1')->setPropertyPath('category');

        $categoryConfig = $this->getConfig(['name1'], ['name1']);
        $categoryConfig->getDefinition()->getField('name1')->setPropertyPath('name');
        $categoryConfig->getSorters()->getField('name1')->setPropertyPath('name');

        $this->prepareFilters('category1.name1');

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $this->getEntityClass('Category'),
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [
                    new EntityDefinitionConfigExtra($this->context->getAction()),
                    new SortersConfigExtra()
                ]
            )
            ->willReturn($categoryConfig);

        $this->processor->process($this->context);

        $this->assertEmpty($this->context->getErrors());
        $this->assertEquals(
            ['category.name' => 'ASC'],
            $this->context->getFilterValues()->get('sort')->getValue()
        );
    }

    public function testProcessWhenSortByNotAllowedAssociationFieldRequested()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);
        $categoryConfig = $this->getConfig(['id', 'name'], ['id']);

        $this->prepareFilters('category.name');

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $this->getEntityClass('Category'),
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [
                    new EntityDefinitionConfigExtra($this->context->getAction()),
                    new SortersConfigExtra()
                ]
            )
            ->willReturn($categoryConfig);

        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "category.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByUnknownAssociationConfigRequested()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);

        $this->prepareFilters('category1.name');

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "category1.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByUnknownAssociationRequested()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category1']);

        $this->prepareFilters('category1.name');

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "category1.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenSortByAssociationRequestedButForNotManageableEntity()
    {
        $this->notManageableClassNames = [$this->getEntityClass('User')];

        $primaryEntityConfig = $this->getEntityDefinitionConfig(['category']);

        $this->prepareFilters('category.name');

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError('sort constraint', 'Sorting by "category.name" field is not supported.')
                    ->setSource(ErrorSource::createByParameter('sort'))
            ],
            $this->context->getErrors()
        );
    }

    /**
     * @param string $sortBy
     */
    protected function prepareFilters($sortBy = '-id')
    {
        $sorterFilter = new SortFilter(DataType::ORDER_BY);
        $filters      = new FilterCollection();
        $filters->add('sort', $sorterFilter);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('sort=' . $sortBy);
        $filterValues = new RestFilterValueAccessor($request);

        // emulate sort normalizer
        $orderBy = [];
        $items = explode(',', $sortBy);
        foreach ($items as $item) {
            $item = trim($item);
            if (0 === strpos($item, '-')) {
                $orderBy[substr($item, 1)] = 'DESC';
            } else {
                $orderBy[$item] = 'ASC';
            }
        }
        $filterValues->get('sort')->setValue($orderBy);

        $this->context->set('filters', $filters);
        $this->context->setFilterValues($filterValues);
    }

    /**
     * @param string $entityShortClass
     *
     * @return string
     */
    protected function getEntityClass($entityShortClass)
    {
        return self::ENTITY_NAMESPACE . $entityShortClass;
    }

    /**
     * @param string[] $fields
     * @param string[] $sortFields
     *
     * @return Config
     */
    protected function getConfig(array $fields = [], array $sortFields = [])
    {
        $config = new Config();
        $config->setDefinition($this->getEntityDefinitionConfig($fields));
        $config->setSorters($this->getSortersConfig($sortFields));

        return $config;
    }

    /**
     * @param string[] $fields
     *
     * @return EntityDefinitionConfig
     */
    protected function getEntityDefinitionConfig(array $fields = [])
    {
        $config = new EntityDefinitionConfig();
        foreach ($fields as $field) {
            $config->addField($field);
        }

        return $config;
    }

    /**
     * @param string[] $fields
     *
     * @return SortersConfig
     */
    protected function getSortersConfig(array $fields = [])
    {
        $config = new SortersConfig();
        foreach ($fields as $field) {
            $config->addField($field);
        }

        return $config;
    }
}
