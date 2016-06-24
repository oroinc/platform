<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class FilterDateRangeConverter extends ConfigValueConverterAbstract
{
    const MIN_DATE = '1900-01-01';

    /** @var DateTimeFormatter */
    protected $formatter;

    /** @var Compiler */
    protected $dateCompiler;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DateHelper */
    protected $dateHelper;

    /** @var array */
    protected static $valueTypesStartVarsMap = [
        AbstractDateFilterType::TYPE_TODAY        => [
            'var_start'             => DateModifierInterface::VAR_TODAY,
            'modify_end'            => null,
            'modify_previous_start' => '- 1 day'
        ],
        AbstractDateFilterType::TYPE_THIS_WEEK    => [
            'var_start'             => DateModifierInterface::VAR_SOW,
            'modify_end'            => '+ 1 week - 1 day',
            'modify_previous_start' => '- 1 week'
        ],
        AbstractDateFilterType::TYPE_THIS_MONTH   => [
            'var_start'             => DateModifierInterface::VAR_SOM,
            'modify_end'            => '+ 1 month  - 1 day',
            'modify_previous_start' => '- 1 month'
        ],
        AbstractDateFilterType::TYPE_THIS_QUARTER => [
            'var_start'             => DateModifierInterface::VAR_SOQ,
            'modify_end'            => '+ 3 month - 1 day',
            'modify_previous_start' => '- 3 month'
        ],
        AbstractDateFilterType::TYPE_THIS_YEAR    => [
            'var_start'             => DateModifierInterface::VAR_SOY,
            'modify_end'            => '+ 1 year - 1 day',
            'modify_previous_start' => '- 1 year'
        ],
    ];

    /**
     * @param DateTimeFormatter   $formatter
     * @param Compiler            $dateCompiler
     * @param TranslatorInterface $translator
     * @param DateHelper          $dateHelper
     */
    public function __construct(
        DateTimeFormatter $formatter,
        Compiler $dateCompiler,
        TranslatorInterface $translator,
        DateHelper $dateHelper
    ) {
        $this->formatter    = $formatter;
        $this->dateCompiler = $dateCompiler;
        $this->translator   = $translator;
        $this->dateHelper   = $dateHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getConvertedValue(array $widgetConfig, $value = null, array $config = [], array $options = [])
    {
        $part                = DateModifierInterface::PART_VALUE;
        $type                = AbstractDateFilterType::TYPE_BETWEEN;
        $cretePreviousPeriod = !empty($config['converter_attributes']['create_previous_period']);
        if (isset($value['type']) && in_array($value['type'], AbstractDateFilterType::$valueTypes)) {
            return $this->processValueTypes($value, $cretePreviousPeriod);
        }
        if (null === $value || ($value['value']['start'] === null && $value['value']['end'] === null)) {
            list($start, $end) = $this->dateHelper->getDateTimeInterval('P1M');
        } else {
            $saveOpenRange = !empty($config['converter_attributes']['save_open_range']);
            list($start, $end, $type) = $this->getPeriodValues($value, $saveOpenRange);
            $start = $this->getCompiledDate($start);
            $end   = $this->getCompiledDate($end);
            
            //Swap start and end dates if end date is behind start date
            if ($end && $start > $end) {
                $e     = $end;
                $end   = $start;
                $start = $e;
            }
            if ($end) {
                $end->setTime(23, 59, 59);
            }
            if ($start) {
                $start->setTime(0, 0, 0);
            }
        }
        $dateData = [
            'start' => $start,
            'end'   => $end,
            'type'  => $type,
            'part'  => $part
        ];

        if ($end && $start && $cretePreviousPeriod) {
            $diff      = $start->diff($end);
            $prevStart = clone $start;
            $prevStart->sub($diff);
            $prevEnd = clone $end;
            $prevEnd->sub($diff);
            $prevStart->setTime(0, 0, 0);
            $prevEnd->setTime(23, 59, 59);

            $dateData['prev_start'] = $prevStart;
            $dateData['prev_end']   = $prevEnd;
        }

        return $dateData;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewValue($value)
    {
        $start = $this->getCompiledDate($value['start']);
        $end   = $this->getCompiledDate($value['end']);

        if (isset($value['part']) && $value['part'] === DateModifierInterface::PART_ALL_TIME) {
            return $this->translator->trans('oro.dashboard.widget.filter.date_range.all_time');
        }

        if ($value['type'] === AbstractDateFilterType::TYPE_THIS_MONTH) {
            return $this->formatter->formatMonth($start);
        }

        if ($value['type'] === AbstractDateFilterType::TYPE_THIS_QUARTER) {
            return $this->formatter->formatQuarter($start);
        }

        if ($value['type'] === AbstractDateFilterType::TYPE_THIS_YEAR) {
            return $this->formatter->formatYear($start);
        }

        if ($value['type'] === AbstractDateFilterType::TYPE_MORE_THAN
            || $value['type'] === AbstractDateFilterType::TYPE_BETWEEN && !$end
        ) {
            return sprintf(
                '%s %s',
                $this->translator->trans('oro.filter.form.label_date_type_more_than'),
                $this->formatter->formatDate($start)
            );
        }
        if ($value['type'] === AbstractDateFilterType::TYPE_LESS_THAN) {
            return sprintf(
                '%s %s',
                $this->translator->trans('oro.filter.form.label_date_type_less_than'),
                $this->formatter->formatDate($end)
            );
        }
        $startDate = $this->formatter->formatDate($start);
        $endDate   = $this->formatter->formatDate($end);

        return $startDate !== $endDate
            ? sprintf('%s - %s', $startDate, $endDate)
            : $startDate;
    }

    /**
     * @param array $value
     *
     * @return array
     */
    protected function getPeriodValues($value, $saveOpenRange)
    {
        $startValue = $value['value']['start'];
        $endValue   = $value['value']['end'];
        $type       = $value['type'];

        if ($type === AbstractDateFilterType::TYPE_LESS_THAN
            || ($type === AbstractDateFilterType::TYPE_BETWEEN && $startValue === null)
        ) {
            if (!$saveOpenRange) {
                $startValue = new \DateTime(self::MIN_DATE, new \DateTimeZone('UTC'));
                $type       = AbstractDateFilterType::TYPE_LESS_THAN;
            } else {
                $type = AbstractDateFilterType::TYPE_BETWEEN;
            }
        }

        if ($type === AbstractDateFilterType::TYPE_MORE_THAN
            || ($type === AbstractDateFilterType::TYPE_BETWEEN && $endValue === null)
        ) {
            if (!$saveOpenRange) {
                $endValue = new \DateTime('now', new \DateTimeZone('UTC'));
                $type     = AbstractDateFilterType::TYPE_MORE_THAN;
            } else {
                $type = AbstractDateFilterType::TYPE_BETWEEN;
            }
        }

        return [$startValue, $endValue, $type];
    }

    /**
     * @param array $value
     *
     * @return array
     */
    protected function processValueTypes(array $value, $cretePreviousPeriod)
    {
        $start = $end = $part = $prevStart = $prevEnd = null;
        $type = isset($value['type']) ? $value['type'] : AbstractDateFilterType::TYPE_BETWEEN;
        if (array_key_exists($value['type'], static::$valueTypesStartVarsMap)) {
            /** @var \Carbon\Carbon $start */
            $start  = $this->dateCompiler->compile(
                sprintf('{{%s}}', static::$valueTypesStartVarsMap[$value['type']]['var_start'])
            );
            $end    = clone $start;
            $modify = static::$valueTypesStartVarsMap[$value['type']]['modify_end'];
            if ($modify) {
                $end->modify($modify);
            }
            $start->setTime(0, 0, 0);
            $end->setTime(23, 59, 59);
            if ($cretePreviousPeriod) {
                $prevStart  = clone $start;
                $prevModify = static::$valueTypesStartVarsMap[$value['type']]['modify_previous_start'];
                if ($prevModify) {
                    $prevStart->modify($prevModify);
                }
                $prevEnd = clone $prevStart;
                if ($modify) {
                    $prevEnd->modify($modify);
                }
                $prevStart->setTime(0, 0, 0);
                $prevEnd->setTime(23, 59, 59);
            }
        }
        if ($value['type'] === AbstractDateFilterType::TYPE_ALL_TIME) {
            $part = DateModifierInterface::PART_ALL_TIME;
        }

        return [
            'start'      => $start,
            'end'        => $end,
            'type'       => $type,
            'part'       => $part,
            'prev_start' => $prevStart,
            'prev_end'   => $prevEnd
        ];
    }
    
    protected function getCompiledDate($value)
    {
        return $value instanceof \DateTime
            ? $value
            : $this->dateCompiler->compile($value);
    }
}
