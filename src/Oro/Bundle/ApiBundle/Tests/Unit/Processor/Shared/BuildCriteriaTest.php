<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;



use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
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

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess($className, $fields, $query, $expected)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturnCallback(function () use ($query) {
                return implode('&', $query);
            });

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(function () use ($fields) {
                $config = new Config();
                $config->setDefinition($this->getEntityDefinitionConfig(array_keys($fields)));
                $config->setFilters($this->getFiltersConfig($fields));

                return $config;
            });

        $this->context->setClassName($className);
        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setCriteria(new Criteria());

        $this->processor->process($this->context);

        list($errors, $criteria) = $expected;

        $this->assertEquals($errors, $this->context->getErrors());
        $this->assertEquals($criteria, $this->context->getCriteria()->getWhereExpression());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processDataProvider()
    {
        $integerFilter = new ComparisonFilter('integer');
        $stringFilter  = new ComparisonFilter('string');

        return [
            'simple filters' => [
                'className' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                'fields' => [
                    'name' => clone $stringFilter,
                    'label' => clone $stringFilter
                ],
                'query' => [
                    'filter[label]=test',
                    'filter[name]=test'
                ],
                'expected' => [
                    [],
                    new CompositeExpression(
                        'AND',
                        [
                            new Comparison('label', '=', 'test'),
                            new Comparison('name', '=', 'test')
                        ]
                    )
                ]
            ],
            'not existing field filter' => [
                'className' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                'fields' => [
                    'name' => clone $stringFilter,
                    'label' => clone $stringFilter
                ],
                'query' => [
                    'filter[label123]=test',
                ],
                'expected' => [
                    [
                        Error::createValidationError(
                            Constraint::FILTER,
                            sprintf('Filter "%s" is not supported.', 'filter[label123]')
                        )->setSource(ErrorSource::createByParameter('filter[label123]'))
                    ],
                    null
                ]
            ],
            'subresource filters' => [
                'className' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'fields' => [
                    'id' => clone $integerFilter,
                    'name' => clone $stringFilter
                ],
                'query' => [
                    'filter[category.name]=test'
                ],
                'expected' => [
                    [],
                    new Comparison('category.name', '=', 'test'),
                ]
            ],
            'subresource filters, all are associations' => [
                'className' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'fields' => [
                    'id' => clone $integerFilter,
                    'name' => clone $stringFilter,
                    'category' => clone $integerFilter
                ],
                'query' => [
                    'filter[products.category]=1'
                ],
                'expected' => [
                    [],
                    new Comparison('products.category', '=', 1),
                ]
            ],
            'subresource filters, all associations, last not found' => [
                'className' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'fields' => [
                    'id' => clone $integerFilter,
                    'name' => clone $stringFilter,
                ],
                'query' => [
                    'filter[products.category]=1'
                ],
                'expected' => [
                    [
                        Error::createValidationError(
                            Constraint::FILTER,
                            sprintf('Filter "%s" is not supported.', 'filter[products.category]')
                        )->setSource(ErrorSource::createByParameter('filter[products.category]'))
                    ],
                    null
                ]
            ],
            'wrong subresource' => [
                'className' => 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'fields' => [
                    'id' => clone $integerFilter,
                    'name' => clone $stringFilter
                ],
                'query' => [
                    'filter[wrongField.name]=test'
                ],
                'expected' => [
                    [
                        Error::createValidationError(
                            Constraint::FILTER,
                            sprintf('Filter "%s" is not supported.', 'filter[wrongField.name]')
                        )->setSource(ErrorSource::createByParameter('filter[wrongField.name]'))
                    ],
                    null
                ]
            ]
        ];
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
     * @param array $fields
     * @return FiltersConfig
     */
    protected function getFiltersConfig(array $fields = [])
    {
        $config = new FiltersConfig();
        foreach ($fields as $field => $filterFieldConfig) {
            $config->addField($field, $filterFieldConfig);
        }

        return $config;
    }
}
