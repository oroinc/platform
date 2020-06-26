<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\DateFilterUtility;
use Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class DateTimeRangeFilterTest extends OrmTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface */
    private $formFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DateFilterUtility */
    private $dateFilterUtility;

    /** @var DateTimeRangeFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->dateFilterUtility = $this->createMock(DateFilterUtility::class);

        $this->filter = new DateTimeRangeFilter($this->formFactory, new FilterUtility(), $this->dateFilterUtility);
    }

    public function testApply(): void
    {
        $fieldName = 'createdDate';

        $this->filter->init('date', [FilterUtility::DATA_NAME_KEY => $fieldName]);

        $data = [
            'type' => DateTimeRangeFilterType::TYPE_BETWEEN,
            'value' => [
                'start' => '2018-01-20',
                'end' => '2020-01-20',
            ]
        ];

        $this->dateFilterUtility->expects($this->once())
            ->method('parseData')
            ->with($fieldName, $data)
            ->willReturn(
                [
                    'type' => DateTimeRangeFilterType::TYPE_BETWEEN,
                    'field' => $fieldName,
                    'date_start' => new \DateTime('2018-01-20T10:00:00', new \DateTimeZone('Europe/Kiev')),
                    'date_end' => new \DateTimeImmutable('2020-01-20T10:00:00', new \DateTimeZone('Europe/Kiev'))
                ]
            );

        $expr = $this->createMock(ExpressionBuilderInterface::class);
        $expr->expects($this->once())
            ->method('gte')
            ->with($fieldName, 'date1');
        $expr->expects($this->once())
            ->method('lt')
            ->with($fieldName, 'date2');

        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $ds->expects($this->any())
            ->method('generateParameterName')
            ->willReturnCallback(
                static function ($name) {
                    static $paramIndex = 0;
                    $paramIndex++;

                    return \sprintf('%s%d', $name, $paramIndex);
                }
            );
        $ds->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                [
                    'date1',
                    new \DateTime('2018-01-20T10:00:00', new \DateTimeZone('Europe/Kiev')),
                    Types::DATETIME_MUTABLE
                ],
                [
                    'date2',
                    new \DateTimeImmutable('2020-01-20T10:00:00', new \DateTimeZone('Europe/Kiev')),
                    Types::DATETIME_IMMUTABLE
                ]
            );
        $ds->expects($this->any())
            ->method('expr')
            ->willReturn($expr);

        $this->filter->apply($ds, $data);
    }
}
