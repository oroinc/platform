<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DashboardBundle\Form\Type\CurrentDateWidgetDateRangeType;
use Oro\Bundle\DashboardBundle\Form\Type\WidgetDateRangeType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class CurrentDateWidgetDateRangeTypeTest extends TypeTestCase
{
    private const CHOICES = [
        'oro.dashboard.widget.filter.current_date_range.choices.today' => AbstractDateFilterType::TYPE_TODAY,
        'oro.dashboard.widget.filter.current_date_range.choices.month_to_date' =>
            AbstractDateFilterType::TYPE_THIS_MONTH,
        'oro.dashboard.widget.filter.current_date_range.choices.quarter_to_date' =>
            AbstractDateFilterType::TYPE_THIS_QUARTER,
        'oro.dashboard.widget.filter.current_date_range.choices.year_to_date' => AbstractDateFilterType::TYPE_THIS_YEAR,
        'oro.dashboard.widget.filter.current_date_range.choices.all_time' =>
            AbstractDateFilterType::TYPE_ALL_TIME,
        'oro.dashboard.widget.filter.current_date_range.choices.custom' => AbstractDateFilterType::TYPE_BETWEEN,
    ];

    private DateModifierProvider $dateModifier;

    private TranslatorInterface|MockObject $translator;

    private EventSubscriberInterface|MockObject $subscriber;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->dateModifier = new DateModifierProvider();
        $this->subscriber = $this->createMock(EventSubscriberInterface::class);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $type = new CurrentDateWidgetDateRangeType($this->translator);
        $filterType = new FilterType($this->translator);
        $dateRangeFilterType = new DateRangeFilterType($this->translator, $this->dateModifier, $this->subscriber);
        $parentType = new WidgetDateRangeType($this->translator);

        return [
            new PreloadedExtension([$type, $parentType, $dateRangeFilterType, $filterType], []),
        ];
    }

    public function testConfigureOptions(): void
    {
        $dependentDateRangeFields = [
            'TYPE_ALL_TIME' => [
                'dateRange2' => 'TYPE_NONE',
                'dateRange3' => 'TYPE_NONE',
            ],
        ];
        $options = [
            'update_dependent_date_range_fields' => $dependentDateRangeFields,
        ];

        $form = $this->factory->create(CurrentDateWidgetDateRangeType::class, null, $options);

        self::assertEquals(self::CHOICES, $form->getConfig()->getOption('operator_choices'));
        self::assertEquals(
            $dependentDateRangeFields,
            $form->getConfig()->getOption('update_dependent_date_range_fields')
        );
    }

    /**
     * @dataProvider getFinishViewDataProvider
     */
    public function testFinishView(array $options, array $expectedDependentDateRangeFields): void
    {
        $view = new FormView();

        $form = $this->factory->create(CurrentDateWidgetDateRangeType::class, null, $options);

        $type = new CurrentDateWidgetDateRangeType($this->translator);
        $type->finishView($view, $form, []);

        self::assertEquals(false, $view->vars['datetime_range_metadata']['autoUpdateBetweenWhenOneDate']);
        self::assertEquals(
            $expectedDependentDateRangeFields,
            $view->vars['datetime_range_metadata']['dependentDateRangeFields']
        );
    }

    public function getFinishViewDataProvider(): array
    {
        return [
            'empty options' => [
                'options' => [],
                'expectedDependentDateRangeFields' => [],
            ],
            'update_dependent_date_range_fields option' => [
                'options' => [
                    'update_dependent_date_range_fields' => [
                        'TYPE_ALL_TIME' => [
                            'dateRange2' => 'TYPE_NONE',
                        ],
                    ],
                ],
                'expectedDependentDateRangeFields' => [
                    AbstractDateFilterType::TYPE_ALL_TIME => [
                        'select[name$="[dateRange2][type]"]' => AbstractDateFilterType::TYPE_NONE,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testSubmitValidData(array $data): void
    {
        $form = $this->factory->create(CurrentDateWidgetDateRangeType::class);

        $form->submit($data);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    public function validDataProvider(): array
    {
        return [
            'today' => [
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_TODAY,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'month-To-Date' => [
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_THIS_MONTH,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'quarter-To-Date' => [
                'options' => [
                    'value_types' => true,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_THIS_QUARTER,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'year-To-Date' => [
                'options' => [
                    'value_types' => true,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_THIS_YEAR,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'all time' => [
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'custom with start and end dates' => [
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'value' => [
                        'start' => new \DateTime('today', new \DateTimeZone('UTC')),
                        'end' => new \DateTime('today', new \DateTimeZone('UTC')),
                    ],
                    'part' => 'value',
                ],
            ],
            'custom with start date' => [
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'value' => [
                        'start' => new \DateTime('today', new \DateTimeZone('UTC')),
                        'end' => null,
                    ],
                    'part' => 'value',
                ],
            ],
            'custom with end date' => [
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'value' => [
                        'start' => null,
                        'end' => new \DateTime('today', new \DateTimeZone('UTC')),
                    ],
                    'part' => 'value',
                ],
            ],
            'custom with empty dates' => [
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'part' => 'value',
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testSubmitInvalidData(array $data): void
    {
        $form = $this->factory->create(CurrentDateWidgetDateRangeType::class);

        $form->submit($data);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function invalidDataProvider(): array
    {
        return [
            'unknown type' => [
                'data' => [
                    'type' => \PHP_INT_MAX,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
        ];
    }
}
