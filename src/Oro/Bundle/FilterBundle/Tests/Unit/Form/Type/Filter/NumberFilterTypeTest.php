<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Bundle\FormBundle\Form\Extension\NumberTypeExtension;
use Oro\Bundle\LocaleBundle\Formatter\Factory\IntlNumberFormatterFactory;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class NumberFilterTypeTest extends AbstractTypeTestCase
{
    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var NumberFilterType */
    private $type;

    private string $defaultLocale;

    protected function setUp(): void
    {
        $translator = $this->createMockTranslator();
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->numberFormatter = new NumberFormatter(
            $this->localeSettings,
            new IntlNumberFormatterFactory($this->localeSettings)
        );
        $this->type = new NumberFilterType($translator, $this->numberFormatter);
        $this->formExtensions[] = new CustomFormExtension([new FilterType($translator)]);
        $this->formExtensions[] = new PreloadedExtension([$this->type], []);

        parent::setUp();

        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->defaultLocale);
    }

    protected function getTypeExtensions(): array
    {
        return [
            new NumberTypeExtension($this->numberFormatter),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType(): AbstractType
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsDataProvider(): array
    {
        return [
            [
                'defaultOptions' => [
                    'field_type' => NumberType::class,
                    'operator_choices' => [
                        'oro.filter.form.label_type_equal' => NumberFilterType::TYPE_EQUAL,
                        'oro.filter.form.label_type_not_equal' => NumberFilterType::TYPE_NOT_EQUAL,
                        'oro.filter.form.label_type_greater_equal' => NumberFilterType::TYPE_GREATER_EQUAL,
                        'oro.filter.form.label_type_greater_than' => NumberFilterType::TYPE_GREATER_THAN,
                        'oro.filter.form.label_type_less_equal' => NumberFilterType::TYPE_LESS_EQUAL,
                        'oro.filter.form.label_type_less_than' => NumberFilterType::TYPE_LESS_THAN,
                        'oro.filter.form.label_type_in' => NumberFilterType::TYPE_IN,
                        'oro.filter.form.label_type_not_in' => NumberFilterType::TYPE_NOT_IN,
                        'oro.filter.form.label_type_empty' => FilterUtility::TYPE_EMPTY,
                        'oro.filter.form.label_type_not_empty' => FilterUtility::TYPE_NOT_EMPTY,
                    ],
                    'data_type' => NumberFilterType::DATA_INTEGER,
                    'formatter_options' => []
                ]
            ]
        ];
    }

    /**
     * @dataProvider bindDataProvider
     */
    public function testBindData(
        array $bindData,
        array $formData,
        array $viewData,
        array $customOptions = []
    ) {
        // NOTE: must be executed after EntityBundle, because it will be fail result

        $locale = 'en';
        $this->localeSettings->expects(self::atLeastOnce())
            ->method('getLocale')
            ->willReturn($locale);

        \Locale::setDefault($locale);

        parent::testBindData($bindData, $formData, $viewData, $customOptions);
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function bindDataProvider(): array
    {
        return [
            'not formatted number' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12345.67890'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 12345.6789],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12,345.6789'],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_options' => ['grouping' => true, 'scale' => 2]
                ],
            ],
            'formatted number' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12,345.68'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 12345.68],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12,345.68'],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_options' => ['grouping' => true, 'scale' => 2]
                ],
            ],
            'integer' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12345'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 12345],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12345'],
                    'formatter_options' => [
                        'decimals' => 0,
                        'grouping' => false,
                        'orderSeparator' => '',
                        'decimalSeparator' => '.',
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_type' => IntegerType::class,
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
            ],
            'integer IN' => [
                'bindData' => ['type' => NumberFilterType::TYPE_IN, 'value' => '1,2,5'],
                'formData' => ['type' => NumberFilterType::TYPE_IN, 'value' => '1,2,5'],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_IN, 'value' => '1,2,5'],
                    'formatter_options' => [
                        'decimals' => 0,
                        'grouping' => false,
                        'orderSeparator' => '',
                        'decimalSeparator' => '.',
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_type' => IntegerType::class,
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
            ],
            'integer NOT IN' => [
                'bindData' => ['type' => NumberFilterType::TYPE_NOT_IN, 'value' => '1,2,5'],
                'formData' => ['type' => NumberFilterType::TYPE_NOT_IN, 'value' => '1,2,5'],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_NOT_IN, 'value' => '1,2,5'],
                    'formatter_options' => [
                        'decimals' => 0,
                        'grouping' => false,
                        'orderSeparator' => '',
                        'decimalSeparator' => '.',
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_type' => IntegerType::class,
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
            ],
            'percent_float' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12.34'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 12.34],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12.34'],
                    'formatter_options' => [
                        'grouping' => true,
                        'orderSeparator' => ',',
                        'decimalSeparator' => '.',
                        'percent' => true
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::PERCENT
                ],
                'customOptions' => [
                    'data_type' => NumberFilterType::PERCENT
                ],
            ],
            'percent_int' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 12],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12'],
                    'formatter_options' => [
                        'grouping' => true,
                        'orderSeparator' => ',',
                        'decimalSeparator' => '.',
                        'percent' => true
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::PERCENT
                ],
                'customOptions' => [
                    'data_type' => NumberFilterType::PERCENT
                ],
            ],
            'money' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12345.67890'],
                'formData' => [
                    'type' => NumberFilterType::TYPE_EQUAL,
                    'value' => 12345.68
                ],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12345.68'],
                    'formatter_options' => [
                        'grouping' => true,
                        'orderSeparator' => ' ',
                        'decimalSeparator' => '.',
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_DECIMAL
                ],
                'customOptions' => [
                    'field_type' => MoneyType::class,
                    'data_type' => NumberFilterType::DATA_DECIMAL,
                    'formatter_options' => [
                        'orderSeparator' => ' '
                    ]
                ],
            ],
            'invalid format' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 'abcd.67890'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 'abcd.67890'],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_type' => MoneyType::class
                ],
            ],
        ];
    }

    /**
     * @dataProvider bindDataWithAnotherLocaleProvider
     */
    public function testBindDataWithAnotherLocale(
        array $bindData,
        array $formData,
        array $viewData,
        array $customOptions
    ) {
        // NOTE: must be executed after EntityBundle, because it will be fail result

        $locale = 'de_DE';
        $this->localeSettings->expects(self::atLeastOnce())
            ->method('getLocale')
            ->willReturn($locale);

        \Locale::setDefault($locale);

        parent::testBindData($bindData, $formData, $viewData, $customOptions);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function bindDataWithAnotherLocaleProvider(): array
    {
        return [
            'not formatted number' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12345,67890'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 12345.6789],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12.345,6789'],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_options' => ['grouping' => true, 'scale' => 2]
                ],
            ],
            'formatted number' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12.345,68'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 12345.68],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12.345,68'],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_options' => ['grouping' => true, 'scale' => 2]
                ],
            ],
            'integer' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12345'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 12345],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12345'],
                    'formatter_options' => [
                        'decimals' => 0,
                        'grouping' => false,
                        'orderSeparator' => '',
                        'decimalSeparator' => ',',
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_type' => IntegerType::class,
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
            ],
            'integer IN' => [
                'bindData' => ['type' => NumberFilterType::TYPE_IN, 'value' => '1,2,5'],
                'formData' => ['type' => NumberFilterType::TYPE_IN, 'value' => '1,2,5'],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_IN, 'value' => '1,2,5'],
                    'formatter_options' => [
                        'decimals' => 0,
                        'grouping' => false,
                        'orderSeparator' => '',
                        'decimalSeparator' => ',',
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_type' => IntegerType::class,
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
            ],
            'integer NOT IN' => [
                'bindData' => ['type' => NumberFilterType::TYPE_NOT_IN, 'value' => '1,2,5'],
                'formData' => ['type' => NumberFilterType::TYPE_NOT_IN, 'value' => '1,2,5'],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_NOT_IN, 'value' => '1,2,5'],
                    'formatter_options' => [
                        'decimals' => 0,
                        'grouping' => false,
                        'orderSeparator' => '',
                        'decimalSeparator' => ',',
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_type' => IntegerType::class,
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
            ],
            'percent_float' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12,34'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 12.34],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12,340'],
                    'formatter_options' => [
                        'grouping' => true,
                        'orderSeparator' => '.',
                        'decimalSeparator' => ',',
                        'percent' => true
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::PERCENT,
                    'limit_decimals' => false
                ],
                'customOptions' => [
                    'data_type' => NumberFilterType::PERCENT,
                    'field_options' => ['scale' => 3]
                ],
            ],
            'decimal' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12,34'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 12.34],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12,340'],
                    'formatter_options' => [
                        'grouping' => true,
                        'orderSeparator' => '.',
                        'decimalSeparator' => ',',
                        'decimals' => 3
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_DECIMAL,
                    'limit_decimals' => false
                ],
                'customOptions' => [
                    'data_type' => NumberFilterType::DATA_DECIMAL,
                    'field_options' => ['scale' => 3]
                ],
            ],
            'percent_int' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 12],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12'],
                    'formatter_options' => [
                        'grouping' => true,
                        'orderSeparator' => '.',
                        'decimalSeparator' => ',',
                        'percent' => true
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::PERCENT,
                    'limit_decimals' => false
                ],
                'customOptions' => [
                    'data_type' => NumberFilterType::PERCENT
                ],
            ],
            'money' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12345,67890'],
                'formData' => [
                    'type' => NumberFilterType::TYPE_EQUAL,
                    'value' => 12345.68
                ],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => '12345,68'],
                    'formatter_options' => [
                        'decimals' => 4,
                        'grouping' => true,
                        'orderSeparator' => ' ',
                        'decimalSeparator' => ',',
                    ],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_DECIMAL
                ],
                'customOptions' => [
                    'field_type' => MoneyType::class,
                    'data_type' => NumberFilterType::DATA_DECIMAL,
                    'formatter_options' => [
                        'decimals' => 4,
                        'orderSeparator' => ' '
                    ]
                ],
            ],
            'invalid format' => [
                'bindData' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 'abcd.67890'],
                'formData' => ['type' => NumberFilterType::TYPE_EQUAL],
                'viewData' => [
                    'value' => ['type' => NumberFilterType::TYPE_EQUAL, 'value' => 'abcd.67890'],
                    'array_separator' => ',',
                    'array_operators' => [NumberFilterType::TYPE_IN, NumberFilterType::TYPE_NOT_IN],
                    'data_type' => NumberFilterType::DATA_INTEGER
                ],
                'customOptions' => [
                    'field_type' => MoneyType::class
                ],
            ],
        ];
    }
}
