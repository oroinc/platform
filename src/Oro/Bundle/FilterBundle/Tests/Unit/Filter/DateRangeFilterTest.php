<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Expression\Date\Lexer;
use Oro\Bundle\FilterBundle\Expression\Date\Parser;
use Oro\Bundle\FilterBundle\Filter\DateFilterUtility;
use Oro\Bundle\FilterBundle\Filter\DateRangeFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Oro\Bundle\FilterBundle\Utils\DateFilterModifier;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class DateRangeFilterTest extends OrmTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface */
    private $formFactory;

    /** @var DateRangeFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $localeSettings = $this->createMock(LocaleSettings::class);
        $localeSettings->expects(self::any())
            ->method('getTimeZone')
            ->willReturn('UTC');

        $compiler = new Compiler(
            new Lexer($this->createMock(TranslatorInterface::class), new DateModifierProvider()),
            new Parser($localeSettings)
        );

        $this->filter = new DateRangeFilter(
            $this->formFactory,
            new FilterUtility(),
            new DateFilterUtility($localeSettings, $compiler),
            $localeSettings,
            new DateFilterModifier($compiler)
        );
    }

    public function testGetForm()
    {
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(DateRangeFilterType::class)
            ->willReturn($form);

        self::assertSame($form, $this->filter->getForm());
    }

    public function testApply()
    {
        $fieldName = 'createdDate';

        $this->filter->init('date', [FilterUtility::DATA_NAME_KEY => $fieldName]);

        $data = [
            'type'  => DateRangeFilterType::TYPE_EQUAL,
            'value' => [
                'start'          => new \DateTime('2018-01-20T00:00:00', new \DateTimeZone('UTC')),
                'start_original' => '2020-01-20'
            ]
        ];

        $expr = $this->createMock(ExpressionBuilderInterface::class);
        $expr->expects(self::once())
            ->method('eq')
            ->with($fieldName, 'date1');

        $paramIndex = 0;
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $ds->expects(self::any())
            ->method('generateParameterName')
            ->willReturnCallback(function ($name) use (&$paramIndex) {
                $paramIndex++;

                return \sprintf('%s%d', $name, $paramIndex);
            });
        $ds->expects(self::once())
            ->method('setParameter')
            ->with('date1', new \DateTime('2018-01-20T00:00:00', new \DateTimeZone('UTC')), Types::DATE_MUTABLE);
        $ds->expects(self::any())
            ->method('expr')
            ->willReturn($expr);

        $this->filter->apply($ds, $data);
    }

    public function testApplyNoType()
    {
        $fieldName = 'createdDate';

        $this->filter->init('date', [FilterUtility::DATA_NAME_KEY => $fieldName]);

        $data = [
            'value' => [
                'start'          => new \DateTime('2018-01-20T00:00:00', new \DateTimeZone('UTC')),
                'start_original' => '2020-01-20'
            ]
        ];

        $expr = $this->createMock(ExpressionBuilderInterface::class);
        $expr->expects(self::once())
            ->method('gte')
            ->with($fieldName, 'date1');

        $paramIndex = 0;
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $ds->expects(self::any())
            ->method('generateParameterName')
            ->willReturnCallback(function ($name) use (&$paramIndex) {
                $paramIndex++;

                return \sprintf('%s%d', $name, $paramIndex);
            });
        $ds->expects(self::once())
            ->method('setParameter')
            ->with('date1', new \DateTime('2018-01-20T00:00:00', new \DateTimeZone('UTC')), Types::DATE_MUTABLE);
        $ds->expects(self::any())
            ->method('expr')
            ->willReturn($expr);

        $this->filter->apply($ds, $data);
    }

    public function testPrepareDataWhenNoValue()
    {
        $data = [];
        self::assertSame(['part' => null], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithNullValue()
    {
        $data = ['value' => null];
        self::assertSame(
            ['value' => null, 'part' => null],
            $this->filter->prepareData($data)
        );
    }

    public function testPrepareDataWithEmptyValue()
    {
        $data = ['value' => []];
        self::assertSame(
            ['value' => [], 'part' => null],
            $this->filter->prepareData($data)
        );
    }

    public function testPrepareDataWithStartValueOnly()
    {
        $data = ['value' => ['start' => '2018-01-20']];
        self::assertEquals(
            [
                'value' => [
                    'start'          => new \DateTime('2018-01-20T00:00:00', new \DateTimeZone('UTC')),
                    'start_original' => '2018-01-20'
                ],
                'part'  => null
            ],
            $this->filter->prepareData($data)
        );
    }

    public function testPrepareDataWithEndValueOnly()
    {
        $data = ['value' => ['end' => '2018-01-20']];
        self::assertEquals(
            [
                'value' => [
                    'end'          => new \DateTime('2018-01-20T00:00:00', new \DateTimeZone('UTC')),
                    'end_original' => '2018-01-20'
                ],
                'part'  => null
            ],
            $this->filter->prepareData($data)
        );
    }

    public function testPrepareDataWithStartAndEndValues()
    {
        $data = ['value' => ['start' => '2018-01-20', 'end' => '2018-01-21']];
        self::assertEquals(
            [
                'value' => [
                    'start'          => new \DateTime('2018-01-20T00:00:00', new \DateTimeZone('UTC')),
                    'end'            => new \DateTime('2018-01-21T00:00:00', new \DateTimeZone('UTC')),
                    'start_original' => '2018-01-20',
                    'end_original'   => '2018-01-21'
                ],
                'part'  => null
            ],
            $this->filter->prepareData($data)
        );
    }

    public function testPrepareDataWithStartAndEndValuesWithVariables()
    {
        $data = ['value' => ['start' => '2018-01-20', 'end' => '2018-01-21']];
        self::assertEquals(
            [
                'value' => [
                    'start'          => new \DateTime('2018-01-20T00:00:00', new \DateTimeZone('UTC')),
                    'end'            => new \DateTime('2018-01-21T00:00:00', new \DateTimeZone('UTC')),
                    'start_original' => '2018-01-20',
                    'end_original'   => '2018-01-21'
                ],
                'part'  => null
            ],
            $this->filter->prepareData($data)
        );
    }

    public function testPrepareDataWhenValueNormalizationIsNotRequiredAndWithStartValueOnly()
    {
        $data = [
            'value' => ['start' => '2018'],
            'part'  => DateModifierInterface::PART_YEAR
        ];
        self::assertSame(
            [
                'value' => [
                    'start'          => 2018,
                    'start_original' => '2018'
                ],
                'part'  => DateModifierInterface::PART_YEAR
            ],
            $this->filter->prepareData($data)
        );
    }

    public function testPrepareDataWhenValueNormalizationIsNotRequiredAndWithEndValueOnly()
    {
        $data = [
            'value' => ['end' => '2018'],
            'part'  => DateModifierInterface::PART_YEAR
        ];
        self::assertSame(
            [
                'value' => [
                    'end'          => 2018,
                    'end_original' => '2018'
                ],
                'part'  => DateModifierInterface::PART_YEAR
            ],
            $this->filter->prepareData($data)
        );
    }

    public function testPrepareDataWhenValueNormalizationIsNotRequired()
    {
        $data = [
            'value' => ['start' => '2018', 'end' => '{{' . DateModifierInterface::VAR_THIS_YEAR . '}} - 1'],
            'part'  => DateModifierInterface::PART_YEAR
        ];
        $currentYear = (int)date('Y');
        self::assertSame(
            [
                'value' => [
                    'start'          => 2018,
                    'end'            => $currentYear - 1,
                    'start_original' => '2018',
                    'end_original'   => '{{' . DateModifierInterface::VAR_THIS_YEAR . '}} - 1'
                ],
                'part'  => DateModifierInterface::PART_YEAR
            ],
            $this->filter->prepareData($data)
        );
    }
}
