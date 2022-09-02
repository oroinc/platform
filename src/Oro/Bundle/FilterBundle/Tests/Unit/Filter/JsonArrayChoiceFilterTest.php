<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\JsonArrayChoiceFilter;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class JsonArrayChoiceFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var JsonArrayChoiceFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new JsonArrayChoiceFilter($this->formFactory, new FilterUtility());
        $this->filter->init('test-filter', [
            FilterUtility::DATA_NAME_KEY => 'field_name'
        ]);
    }

    private function getFilterDatasource(AbstractPlatform $platform): OrmFilterDatasourceAdapter
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getExpressionBuilder')
            ->willReturn(new Query\Expr());
        $connection = $this->createMock(Connection::class);
        $em->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        return new OrmFilterDatasourceAdapter(new QueryBuilder($em));
    }

    private function parseQueryCondition(OrmFilterDatasourceAdapter $ds): ?string
    {
        $qb = $ds->getQueryBuilder();

        $parameters = [];
        /* @var Query\Parameter $param */
        foreach ($qb->getParameters() as $param) {
            $parameters[':' . $param->getName()] = $param->getValue();
        }

        $parts = $qb->getDQLParts();
        if (!$parts['where']) {
            return null;
        }

        $parameterValues = array_map(
            function ($parameterValue) {
                if (is_array($parameterValue)) {
                    return implode(',', $parameterValue);
                }
                if (is_bool($parameterValue)) {
                    return $parameterValue ? 'true' : 'false';
                }

                return $parameterValue;
            },
            array_values($parameters)
        );

        return str_replace(
            array_keys($parameters),
            $parameterValues,
            (string)$parts['where']
        );
    }

    public function testGetMetadata(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formView = new FormView();
        $formView->children['type'] = new FormView($formView);
        $formView->children['type']->vars['choices'] = [];
        $formView->children['value'] = new FormView($formView);
        $formView->vars['populate_default'] = true;
        $formView->children['value']->vars['choices'] = [
            new ChoiceView(null, 'val1', 'label1'),
            new ChoiceView(null, 'val2', 'label2')
        ];
        $formView->children['value']->vars['multiple'] = false;

        $this->formFactory->expects($this->any())
            ->method('create')
            ->willReturn($form);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        $this->assertEquals(
            [
                'name'            => 'test-filter',
                'label'           => 'Test-filter',
                'choices'         => [
                    ['label' => 'label1', 'value' => 'val1'],
                    ['label' => 'label2', 'value' => 'val2']
                ],
                'lazy'            => false,
                'populateDefault' => true,
                'type'            => 'select'
            ],
            $this->filter->getMetadata()
        );
    }

    public function testGetMetadataForMultiple(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formView = new FormView();
        $formView->children['type'] = new FormView($formView);
        $formView->children['type']->vars['choices'] = [];
        $formView->children['value'] = new FormView($formView);
        $formView->vars['populate_default'] = false;
        $formView->children['value']->vars['choices'] = [
            new ChoiceView(null, 'val1', 'label1'),
            new ChoiceView(null, 'val2', 'label2')
        ];
        $formView->children['value']->vars['multiple'] = true;

        $this->formFactory->expects($this->any())
            ->method('create')
            ->willReturn($form);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        $this->assertEquals(
            [
                'name'            => 'test-filter',
                'label'           => 'Test-filter',
                'choices'         => [
                    ['label' => 'label1', 'value' => 'val1'],
                    ['label' => 'label2', 'value' => 'val2']
                ],
                'lazy'            => false,
                'populateDefault' => false,
                'type'            => 'multiselect'
            ],
            $this->filter->getMetadata()
        );
    }

    /**
     * @dataProvider applyProvider
     */
    public function testApply(array $data, ?string $expected): void
    {
        $ds = $this->getFilterDatasource(new MySqlPlatform());
        $this->filter->apply($ds, $data);

        $this->assertSame($expected, $this->parseQueryCondition($ds));
    }

    public function applyProvider(): array
    {
        return [
            [['value' => [], 'type' => null], null],
            [['value' => ['val1'], 'type' => null], 'field_name LIKE %"val1"%'],
            [['value' => ['val1', 'val2'], 'type' => null], 'field_name LIKE %"val1"% OR field_name LIKE %"val2"%']
        ];
    }

    /**
     * @dataProvider applyForPostgreSqlProvider
     */
    public function testApplyForPostgreSql(array $data, ?string $expected)
    {
        $ds = $this->getFilterDatasource(new PostgreSQL94Platform());

        $this->filter->apply($ds, $data);

        $this->assertSame($expected, $this->parseQueryCondition($ds));
    }

    public function applyForPostgreSqlProvider(): array
    {
        return [
            [['value' => [], 'type' => null], null],
            [['value' => ['val1'], 'type' => null], 'ARRAY_CONTAINS(field_name, "val1") = true'],
            [
                ['value' => ['val1', 'val2'], 'type' => null],
                'ARRAY_CONTAINS(field_name, "val1") = true OR ARRAY_CONTAINS(field_name, "val2") = true'
            ],
            [['value' => [1], 'type' => null], 'ARRAY_CONTAINS(field_name, 1) = true'],
            [
                ['value' => [1, 2], 'type' => null],
                'ARRAY_CONTAINS(field_name, 1) = true OR ARRAY_CONTAINS(field_name, 2) = true'
            ]
        ];
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParseData(mixed $data, mixed $expected)
    {
        $this->assertEquals(
            $expected,
            ReflectionUtil::callMethod($this->filter, 'parseData', [$data])
        );
    }

    public function parseDataProvider(): array
    {
        return [
            'invalid data, no array'               => [
                false,
                false
            ],
            'invalid data, no value'               => [
                [],
                false
            ],
            'invalid data, empty string value'     => [
                ['value' => ''],
                false
            ],
            'invalid data, empty array value'      => [
                ['value' => []],
                false
            ],
            'invalid data, empty collection value' => [
                ['value' => new ArrayCollection([])],
                false
            ],
            'scalar value'                         => [
                ['value' => 'test'],
                ['value' => ['test'], 'type' => null]
            ],
            'array value'                          => [
                ['value' => ['val1', 'val2']],
                ['value' => ['val1', 'val2'], 'type' => null]
            ],
            'collection value'                     => [
                ['value' => new ArrayCollection(['val1', 'val2'])],
                ['value' => ['val1', 'val2'], 'type' => null]
            ],
        ];
    }
}
