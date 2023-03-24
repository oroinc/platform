<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetDateRangeType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetDateRangeTypeTest extends TypeTestCase
{
    private const DEFAULT_CHOICES = [
        'oro.filter.form.label_date_type_between' => AbstractDateFilterType::TYPE_BETWEEN,
        'oro.filter.form.label_date_type_more_than' => AbstractDateFilterType::TYPE_MORE_THAN,
        'oro.filter.form.label_date_type_less_than' => AbstractDateFilterType::TYPE_LESS_THAN,
    ];

    private const VALUE_TYPES_CHOICES = [
        'oro.dashboard.widget.filter.date_range.today' => AbstractDateFilterType::TYPE_TODAY,
        'oro.dashboard.widget.filter.date_range.this_week' => AbstractDateFilterType::TYPE_THIS_WEEK,
        'oro.dashboard.widget.filter.date_range.this_month' => AbstractDateFilterType::TYPE_THIS_MONTH,
        'oro.dashboard.widget.filter.date_range.this_quarter' =>
            AbstractDateFilterType::TYPE_THIS_QUARTER,
        'oro.dashboard.widget.filter.date_range.this_year' => AbstractDateFilterType::TYPE_THIS_YEAR,
    ];

    private DateModifierProvider $dateModifier;

    private TranslatorInterface|MockObject $translator;

    private EventSubscriberInterface|MockObject $subscriber;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this
            ->translator
            ->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->dateModifier = new DateModifierProvider();
        $this->subscriber = $this->createMock(EventSubscriberInterface::class);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $type = new WidgetDateRangeType($this->translator);
        $filterType = new FilterType($this->translator);
        $parentType = new DateRangeFilterType($this->translator, $this->dateModifier, $this->subscriber);

        return [
            new PreloadedExtension([$type, $parentType, $filterType], []),
        ];
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptions(array $options, array $expectedChoices): void
    {
        $form = $this->factory->create(WidgetDateRangeType::class, null, $options);

        self::assertEquals($expectedChoices, $form->getConfig()->getOption('operator_choices'));
    }

    public function configureOptionsDataProvider(): array
    {
        return [
            'without options' => [
                'options' => [],
                'expectedChoices' => self::DEFAULT_CHOICES,
            ],
            'with none_value option' => [
                'options' => [
                    'none_value' => true,
                ],
                'expectedChoices' => array_merge(
                    self::DEFAULT_CHOICES,
                    [
                        'oro.dashboard.widget.filter.date_range.none' => AbstractDateFilterType::TYPE_NONE,
                    ],
                )
            ],
            'with value_types option' => [
                'options' => [
                    'value_types' => true,
                ],
                'expectedChoices' => array_merge(
                    self::DEFAULT_CHOICES,
                    self::VALUE_TYPES_CHOICES,
                    [
                        'oro.dashboard.widget.filter.date_range.all_time' =>
                            AbstractDateFilterType::TYPE_ALL_TIME, // added by default
                    ],
                )
            ],
            'with all_time_value === false' => [
                'options' => [
                    'value_types' => true,
                    'all_time_value' => false,
                ],
                'expectedChoices' => array_merge(
                    self::DEFAULT_CHOICES,
                    self::VALUE_TYPES_CHOICES,
                )
            ],
            'with all_time_value === true' => [
                'options' => [
                    'value_types' => true,
                    'all_time_value' => true,
                ],
                'expectedChoices' => array_merge(
                    self::DEFAULT_CHOICES,
                    self::VALUE_TYPES_CHOICES,
                    [
                        'oro.dashboard.widget.filter.date_range.all_time' =>
                            AbstractDateFilterType::TYPE_ALL_TIME,
                    ],
                )
            ],
            'with all_time_value === true and value_types === false' => [
                'options' => [
                    'value_types' => false,
                    'all_time_value' => true,
                ],
                'expectedChoices' => self::DEFAULT_CHOICES,
            ],
        ];
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testSubmitValidData(array $options, array $data): void
    {
        $form = $this->factory->create(WidgetDateRangeType::class, null, $options);

        $form->submit($data);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testSubmitInvalidData(array $options, array $data): void
    {
        $form = $this->factory->create(WidgetDateRangeType::class, null, $options);

        $form->submit($data);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function validDataProvider(): array
    {
        return [
            'with value none' => [
                'options' => [
                    'none_value' => true,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_NONE,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'with value types and data type today' => [
                'options' => [
                    'value_types' => true,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_TODAY,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'with value types and data type this week' => [
                'options' => [
                    'value_types' => true,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_THIS_WEEK,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'with value types and data type this month' => [
                'options' => [
                    'value_types' => true,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_THIS_MONTH,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'with value types and data type this quarter' => [
                'options' => [
                    'value_types' => true,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_THIS_QUARTER,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'with value types and data type this year' => [
                'options' => [
                    'value_types' => true,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_THIS_YEAR,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'with value types and all time option data type all time' => [
                'options' => [
                    'value_types' => true,
                    'all_time_value' => true,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
        ];
    }

    public function invalidDataProvider(): array
    {
        return [
            'without value none' => [
                'options' => [
                    'none_value' => false,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_NONE,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'without value types and data type today' => [
                'options' => [
                    'value_types' => false,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_TODAY,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'without value types and data type this week' => [
                'options' => [
                    'value_types' => false,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_THIS_WEEK,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'without value types and data type this month' => [
                'options' => [
                    'value_types' => false,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_THIS_MONTH,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'without value types and data type this quarter' => [
                'options' => [
                    'value_types' => false,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_THIS_QUARTER,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'without value types and data type this year' => [
                'options' => [
                    'value_types' => false,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_THIS_YEAR,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'without all time option data type all time' => [
                'options' => [
                    'all_time_value' => true,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'without value types and all time option data type all time' => [
                'options' => [
                    'value_types' => false,
                    'all_time_value' => true,
                ],
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
        ];
    }
}
