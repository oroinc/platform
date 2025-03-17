<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Carbon\Carbon;
use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The dashboard widget configuration converter for enter a date range value.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FilterDateRangeConverter extends ConfigValueConverterAbstract
{
    public const MIN_DATE = '1900-01-01';
    public const TODAY = 'today';

    protected static array $valueTypesStartVarsMap = [
        AbstractDateFilterType::TYPE_TODAY => [
            'var_start' => DateModifierInterface::VAR_TODAY,
            'modify_end' => null,
            'modify_previous_start' => '- 1 day'
        ],
        AbstractDateFilterType::TYPE_THIS_WEEK => [
            'var_start' => DateModifierInterface::VAR_SOW,
            'modify_end' => '+ 1 week - 1 day',
            'modify_previous_start' => '- 1 week'
        ],
        AbstractDateFilterType::TYPE_THIS_MONTH => [
            'var_start' => DateModifierInterface::VAR_SOM,
            'modify_end' => '+ 1 month  - 1 day',
            'modify_previous_start' => '- 1 month'
        ],
        AbstractDateFilterType::TYPE_THIS_QUARTER => [
            'var_start' => DateModifierInterface::VAR_SOQ,
            'modify_end' => '+ 3 month - 1 day',
            'modify_previous_start' => '- 3 month'
        ],
        AbstractDateFilterType::TYPE_THIS_YEAR => [
            'var_start' => DateModifierInterface::VAR_SOY,
            'modify_end' => '+ 1 year - 1 day',
            'modify_previous_start' => '- 1 year'
        ],
    ];

    public function __construct(
        protected DateTimeFormatterInterface $formatter,
        protected Compiler $dateCompiler,
        protected TranslatorInterface $translator
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function getConvertedValue(
        array $widgetConfig,
        mixed $value = null,
        array $config = [],
        array $options = []
    ): mixed {
        if (null === $value) {
            $value = $this->getDefaultValue($config);
        }

        if (isset($value['type']) && AbstractDateFilterType::TYPE_NONE === $value['type']) {
            return ['start' => null, 'end' => null, 'type' => $value['type']];
        }

        $createPreviousPeriod = !empty($config['converter_attributes']['create_previous_period']);
        $todayAsEndDateTypes = $config['converter_attributes']['today_as_end_date_for'] ?? [];
        $todayAsEndDateTypes = array_map(
            fn (string $dateType) => $this->getDateTypeConstant($dateType),
            $todayAsEndDateTypes
        );
        if (isset($value['type']) && \in_array($value['type'], AbstractDateFilterType::$valueTypes, true)) {
            return $this->processValueTypes($value, $createPreviousPeriod, $todayAsEndDateTypes);
        }

        if (null === $value || (null === $value['value']['start'] && null === $value['value']['end'])) {
            return $this->processValueTypes(
                [
                    'type' => empty($config['options']['value_types'])
                        ? AbstractDateFilterType::TYPE_ALL_TIME
                        : AbstractDateFilterType::TYPE_THIS_MONTH,
                    'value' => ['start' => null, 'end' => null],
                    'part' => DateModifierInterface::PART_VALUE
                ],
                $createPreviousPeriod,
                $todayAsEndDateTypes
            );
        }

        $saveOpenRange = !empty($config['converter_attributes']['save_open_range']);
        [$start, $end, $type] = $this->getPeriodValues($value, $saveOpenRange);
        $start = $this->getCompiledDate($start);
        $end = $this->getCompiledDate($end);

        if (\in_array($type, $todayAsEndDateTypes, true)) {
            $end = $this->getTodayDateTime();
        }

        //Swap start and end dates if end date is behind start date
        if ($end && $start > $end) {
            $e = $end;
            $end = $start;
            $start = $e;
        }

        $start?->setTime(0, 0);

        $dateData = ['start' => $start, 'type' => $type, 'part' => DateModifierInterface::PART_VALUE];

        if ($end) {
            /**
             * Solves a problem "last second of the day"
             * {@see \Oro\Bundle\FilterBundle\Filter\AbstractDateFilter} class description for more info
             */
            $lastSecondModifier = \DateInterval::createFromDateString('1 day');
            $end->setTime(0, 0, 0)->add($lastSecondModifier);
            $dateData['last_second_modifier'] = $lastSecondModifier;
        }

        $dateData['end'] = $end;

        if ($end && $start && $createPreviousPeriod) {
            $diff = $start->diff($end);
            $prevStart = clone $start;
            $prevStart->sub($diff);
            $prevEnd = clone $end;
            $prevEnd->sub($diff);
            $prevStart->setTime(0, 0, 0);
            $prevEnd->setTime(0, 0, 0)->add($lastSecondModifier);

            $dateData['prev_start'] = $prevStart;
            $dateData['prev_end'] = $prevEnd;
        }

        return $dateData;
    }

    #[\Override]
    public function getFormValue(array $config, mixed $value): mixed
    {
        if (null === $value) {
            $value = $this->getDefaultValue($config);
        }

        return $value;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function getViewValue(mixed $value): mixed
    {
        $start = $this->getCompiledDate($value['start'] ?? '');
        $end = $this->getCompiledDate($value['end'] ?? '');

        if (isset($value['last_second_modifier'])) {
            /**
             * Reverts end date to the original value after the problem "last second of the day" is solved
             * {@see FilterDateRangeConverter::getConvertedValue}
             */
            $end?->sub($value['last_second_modifier']);
        }

        if (\is_array($value)) {
            if (isset($value['part']) && DateModifierInterface::PART_ALL_TIME === $value['part']) {
                return $this->translator->trans('oro.dashboard.widget.filter.date_range.all_time');
            }

            if (AbstractDateFilterType::TYPE_NONE === $value['type']) {
                return $this->translator->trans('oro.dashboard.widget.filter.date_range.none');
            }

            if (AbstractDateFilterType::TYPE_THIS_MONTH === $value['type']) {
                return $this->formatter->formatMonth($start);
            }

            if (AbstractDateFilterType::TYPE_THIS_QUARTER === $value['type']) {
                return $this->formatter->formatQuarter($start);
            }

            if (AbstractDateFilterType::TYPE_THIS_YEAR === $value['type']) {
                return $this->formatter->formatYear($start);
            }

            if (AbstractDateFilterType::TYPE_MORE_THAN === $value['type']
                || (AbstractDateFilterType::TYPE_BETWEEN === $value['type'] && !$end)
            ) {
                return \sprintf(
                    '%s %s',
                    $this->translator->trans('oro.filter.form.label_date_type_more_than'),
                    $this->formatter->formatDate($start)
                );
            }
            if (AbstractDateFilterType::TYPE_LESS_THAN === $value['type']
                || (AbstractDateFilterType::TYPE_BETWEEN === $value['type'] && !$start)
            ) {
                return \sprintf(
                    '%s %s',
                    $this->translator->trans('oro.filter.form.label_date_type_less_than'),
                    $this->formatter->formatDate($end)
                );
            }
        }
        $startDate = $this->formatter->formatDate($start);
        $endDate = $this->formatter->formatDate($end);

        return $startDate !== $endDate
            ? \sprintf('%s - %s', $startDate, $endDate)
            : $startDate;
    }

    protected function getPeriodValues(array $value, bool $saveOpenRange): array
    {
        $startValue = $value['value']['start'];
        $endValue = $value['value']['end'];
        $type = $value['type'];

        if (AbstractDateFilterType::TYPE_LESS_THAN === $type
            || (AbstractDateFilterType::TYPE_BETWEEN === $type && null === $startValue)
        ) {
            if ($saveOpenRange) {
                $type = AbstractDateFilterType::TYPE_BETWEEN;
            } else {
                $type = AbstractDateFilterType::TYPE_LESS_THAN;
                $startValue = new \DateTime(self::MIN_DATE, new \DateTimeZone('UTC'));
            }
        }

        if (AbstractDateFilterType::TYPE_MORE_THAN === $type
            || (AbstractDateFilterType::TYPE_BETWEEN === $type && null === $endValue)
        ) {
            if ($saveOpenRange) {
                $type = AbstractDateFilterType::TYPE_BETWEEN;
            } else {
                $type = AbstractDateFilterType::TYPE_MORE_THAN;
                $endValue = null;
            }
        }

        return [$startValue, $endValue, $type];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processValueTypes(
        array $value,
        bool $createPreviousPeriod,
        array $todayAsEndDateTypes = []
    ): array {
        $start = null;
        $end = null;
        $part = null;
        $prevStart = null;
        $prevEnd = null;
        $lastSecondModifier = null;
        $type = $value['type'] ?? AbstractDateFilterType::TYPE_BETWEEN;
        if (\array_key_exists($value['type'], self::$valueTypesStartVarsMap)) {
            /** @var Carbon $start */
            $start = $this->dateCompiler->compile(
                \sprintf('{{%s}}', self::$valueTypesStartVarsMap[$value['type']]['var_start'])
            );
            $end = clone $start;
            $modify = self::$valueTypesStartVarsMap[$value['type']]['modify_end'];
            if ($modify) {
                $end->modify($modify);
            }
            $start->setTime(0, 0, 0);
            /**
             * Solves a problem "last second of the day"
             * {@see \Oro\Bundle\FilterBundle\Filter\AbstractDateFilter} class description for more info
             */
            $lastSecondModifier = \DateInterval::createFromDateString('1 day');
            $end->setTime(0, 0, 0)->add($lastSecondModifier);
            if ($createPreviousPeriod) {
                $prevStart = clone $start;
                $prevModify = self::$valueTypesStartVarsMap[$value['type']]['modify_previous_start'];
                if ($prevModify) {
                    $prevStart->modify($prevModify);
                }
                $prevEnd = clone $prevStart;
                if ($modify) {
                    $prevEnd->modify($modify);
                }
                $prevStart->setTime(0, 0, 0);
                /**
                 * Solves a problem "last second of the day"
                 * {@see \Oro\Bundle\FilterBundle\Filter\AbstractDateFilter} class description for more info
                 */
                $prevEnd->setTime(0, 0, 0)->add($lastSecondModifier);
            }
        }
        if (AbstractDateFilterType::TYPE_ALL_TIME === $value['type']) {
            $part = DateModifierInterface::PART_ALL_TIME;
        }

        if (\in_array($type, $todayAsEndDateTypes, true)) {
            $end = $this->getTodayDateTime();
            /**
             * Solves a problem "last second of the day"
             * {@see \Oro\Bundle\FilterBundle\Filter\AbstractDateFilter} class description for more info
             */
            $lastSecondModifier = \DateInterval::createFromDateString('1 day');
            $end->setTime(0, 0, 0)->add($lastSecondModifier);
        }

        $dateData = [
            'start' => $start,
            'end' => $end,
            'type' => $type,
            'part' => $part,
            'prev_start' => $prevStart,
            'prev_end' => $prevEnd,
        ];
        if ($lastSecondModifier) {
            $dateData['last_second_modifier'] = $lastSecondModifier;
        }

        return $dateData;
    }

    protected function getCompiledDate(mixed $value): mixed
    {
        return $value instanceof \DateTime
            ? $value
            : $this->dateCompiler->compile((string)$value);
    }

    protected function getDefaultValue(array $config = []): ?array
    {
        $defaultValue = null;
        $defaultSelected = $config['converter_attributes']['default_selected'] ?? null;
        $defaultSelected = $this->getDateTypeConstant($defaultSelected);

        $possibleDefaults = array_merge(AbstractDateFilterType::$valueTypes, [AbstractDateFilterType::TYPE_NONE]);
        if (\in_array($defaultSelected, $possibleDefaults, true)) {
            $defaultValue = [
                'type' => $defaultSelected,
                'value' => ['start' => null, 'end' => null],
                'part' => 'value'
            ];
        }

        return $defaultValue;
    }

    private function getDateTypeConstant(string|int|null $dateType): string|int|null
    {
        if (null !== $dateType) {
            $constantName = AbstractDateFilterType::class . '::' . $dateType;
            if (\defined($constantName)) {
                return \constant($constantName);
            }
        }

        return $dateType;
    }

    private function getTodayDateTime(): \DateTime
    {
        return Carbon::today(new \DateTimeZone('UTC'));
    }
}
