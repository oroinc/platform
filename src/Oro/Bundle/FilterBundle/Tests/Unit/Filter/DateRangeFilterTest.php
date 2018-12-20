<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\DateFilterUtility;
use Oro\Bundle\FilterBundle\Filter\DateRangeFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class DateRangeFilterTest extends OrmTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface */
    private $formFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DateFilterUtility */
    private $dateFilterUtility;

    /** @var DateRangeFilter */
    private $filter;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->dateFilterUtility = $this->createMock(DateFilterUtility::class);

        $this->filter = new DateRangeFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->dateFilterUtility
        );
    }

    public function testGetForm()
    {
        $form = $this->createMock(DateRangeFilterType::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(DateRangeFilterType::class)
            ->willReturn($form);

        self::assertSame($form, $this->filter->getForm());
    }

    public function testApply()
    {
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $expr = $this->createMock(ExpressionBuilderInterface::class);
        $fieldName = 'createdDate';
        $data = [
            'type'  => DateRangeFilterType::TYPE_EQUAL,
            'value' => [
                'start' => '2018-01-20'
            ]
        ];
        $parsedData = [
            'type'       => DateRangeFilterType::TYPE_EQUAL,
            'field'      => $fieldName,
            'date_start' => new \DateTime('2018-01-20T10:00:00', new \DateTimeZone('Europe/Kiev')),
            'date_end'   => null
        ];
        $this->filter->init('date', [FilterUtility::DATA_NAME_KEY => $fieldName]);

        $this->dateFilterUtility->expects(self::once())
            ->method('parseData')
            ->with($fieldName, $data)
            ->willReturn($parsedData);
        $paramIndex = 0;
        $ds->expects(self::any())
            ->method('generateParameterName')
            ->willReturnCallback(function ($name) use (&$paramIndex) {
                $paramIndex++;

                return \sprintf('%s%d', $name, $paramIndex);
            });
        $ds->expects(self::once())
            ->method('setParameter')
            ->with('date1', new \DateTime('2018-01-20T00:00:00', new \DateTimeZone('UTC')), Type::DATE);
        $ds->expects(self::any())
            ->method('expr')
            ->willReturn($expr);
        $expr->expects(self::once())
            ->method('eq')
            ->with($fieldName, 'date1');

        $this->filter->apply($ds, $data);
    }
}
