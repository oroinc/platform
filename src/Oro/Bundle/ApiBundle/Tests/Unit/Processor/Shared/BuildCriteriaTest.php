<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildCriteria;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class BuildCriteriaTest extends GetListProcessorOrmRelatedTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var  BuildCriteria */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->context->setAction('get_list');

        $this->processor = new BuildCriteria($this->configProvider, $this->doctrineHelper);
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

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasQuery());
    }

    public function testProcessFilteringByPrimaryEntityFields()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string', 'label' => 'string']);

        $request = $this->getRequest('filter[label]=val1&filter[name]=val2');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setClassName($this->getEntityClass('Category'));
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());

        $this->processor->process($this->context);

        $this->assertEquals(
            new CompositeExpression(
                'AND',
                [
                    new Comparison('label', '=', 'val1'),
                    new Comparison('name', '=', 'val2')
                ]
            ),
            $this->context->getCriteria()->getWhereExpression()
        );
        $this->assertCount(0, $this->context->getErrors());
    }

    public function testProcessFilteringByUnknownPrimaryEntityField()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string', 'label' => 'string']);

        $request = $this->getRequest('filter[label1]=test');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setClassName($this->getEntityClass('Category'));
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());

        $this->processor->process($this->context);

        $this->assertNull(
            $this->context->getCriteria()->getWhereExpression()
        );
        $this->assertEquals(
            [
                Error::createValidationError(
                    Constraint::FILTER,
                    sprintf('Filter "%s" is not supported.', 'filter[label1]')
                )->setSource(ErrorSource::createByParameter('filter[label1]'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessFilteringByPrimaryEntityFieldWhichCannotBuUsedForFiltering()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['name', 'label']);
        $primaryEntityFilters = $this->getFiltersConfig(['name' => 'string']);

        $request = $this->getRequest('filter[label]=test');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setClassName($this->getEntityClass('Category'));
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());

        $this->processor->process($this->context);

        $this->assertNull(
            $this->context->getCriteria()->getWhereExpression()
        );
        $this->assertEquals(
            [
                Error::createValidationError(
                    Constraint::FILTER,
                    sprintf('Filter "%s" is not supported.', 'filter[label]')
                )->setSource(ErrorSource::createByParameter('filter[label]'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessFilteringByRelatedEntityField()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category.name]=test');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());

        $this->processor->process($this->context);

        $this->assertEquals(
            new Comparison('category.name', '=', 'test'),
            $this->context->getCriteria()->getWhereExpression()
        );
        $this->assertCount(0, $this->context->getErrors());
    }

    public function testProcessFilteringByRelatedEntityFieldWhenAssociationDoesNotExist()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category1.name]=test');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());

        $this->processor->process($this->context);

        $this->assertNull(
            $this->context->getCriteria()->getWhereExpression()
        );
        $this->assertEquals(
            [
                Error::createValidationError(
                    Constraint::FILTER,
                    sprintf('Filter "%s" is not supported.', 'filter[category1.name]')
                )->setSource(ErrorSource::createByParameter('filter[category1.name]'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessFilteringByRelatedEntityFieldWhenAssociationIsRenamed()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category1']);
        $primaryEntityConfig->getField('category1')->setPropertyPath('category');
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category1.name]=test');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(
                    ['name'],
                    ['name' => 'string']
                )
            );

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());

        $this->processor->process($this->context);

        $this->assertEquals(
            new Comparison('category.name', '=', 'test'),
            $this->context->getCriteria()->getWhereExpression()
        );
        $this->assertCount(0, $this->context->getErrors());
    }

    public function testProcessFilteringByRenamedRelatedEntityField()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category']);
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category.name1]=test');

        $categoryConfig = $this->getConfig(
            ['name1'],
            ['name1' => 'string']
        );
        $categoryConfig->getDefinition()->getField('name1')->setPropertyPath('name');
        $categoryConfig->getFilters()->getField('name1')->setPropertyPath('name');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($categoryConfig);

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());

        $this->processor->process($this->context);

        $this->assertEquals(
            new Comparison('category.name', '=', 'test'),
            $this->context->getCriteria()->getWhereExpression()
        );
        $this->assertCount(0, $this->context->getErrors());
    }

    public function testProcessFilteringByRenamedAssociationAndRenamedRelatedEntityField()
    {
        $primaryEntityConfig = $this->getEntityDefinitionConfig(['id', 'category1']);
        $primaryEntityConfig->getField('category1')->setPropertyPath('category');
        $primaryEntityFilters = $this->getFiltersConfig();

        $request = $this->getRequest('filter[category1.name1]=test');

        $categoryConfig = $this->getConfig(
            ['name1'],
            ['name1' => 'string']
        );
        $categoryConfig->getDefinition()->getField('name1')->setPropertyPath('name');
        $categoryConfig->getFilters()->getField('name1')->setPropertyPath('name');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($categoryConfig);

        $this->context->setClassName($this->getEntityClass('User'));
        $this->context->setConfig($primaryEntityConfig);
        $this->context->setConfigOfFilters($primaryEntityFilters);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria($this->getCriteria());

        $this->processor->process($this->context);

        $this->assertEquals(
            new Comparison('category.name', '=', 'test'),
            $this->context->getCriteria()->getWhereExpression()
        );
        $this->assertCount(0, $this->context->getErrors());
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
     * @param array    $filterFields
     *
     * @return Config
     */
    protected function getConfig(array $fields = [], array $filterFields = [])
    {
        $config = new Config();
        $config->setDefinition($this->getEntityDefinitionConfig($fields));
        $config->setFilters($this->getFiltersConfig($filterFields));

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
     * @param array $filterFields
     *
     * @return FiltersConfig
     */
    protected function getFiltersConfig(array $filterFields = [])
    {
        $config = new FiltersConfig();
        foreach ($filterFields as $field => $dataType) {
            $config->addField($field)->setDataType($dataType);
        }

        return $config;
    }

    /**
     * @param $queryString
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequest($queryString)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn($queryString);

        return $request;
    }

    /**
     * @return Criteria
     */
    protected function getCriteria()
    {
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        return new Criteria($resolver);
    }
}
