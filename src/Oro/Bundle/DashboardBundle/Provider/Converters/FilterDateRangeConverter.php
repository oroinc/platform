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
        AbstractDateFilterType::TYPE_TODAY        => [DateModifierInterface::VAR_TODAY, null],
        AbstractDateFilterType::TYPE_THIS_WEEK    => [DateModifierInterface::VAR_SOW, '+ 1 week - 1 day'],
        AbstractDateFilterType::TYPE_THIS_MONTH   => [DateModifierInterface::VAR_SOM, '+ 1 month  - 1 day'],
        AbstractDateFilterType::TYPE_THIS_QUARTER => [DateModifierInterface::VAR_SOQ, '+ 3 month - 1 day'],
        AbstractDateFilterType::TYPE_THIS_YEAR    => [DateModifierInterface::VAR_SOY, '+ 1 year - 1 day'],
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
        $part = DateModifierInterface::PART_VALUE;
        $type = AbstractDateFilterType::TYPE_BETWEEN;
        if (isset($value['type']) && in_array($value['type'], AbstractDateFilterType::$valueTypes)) {
            list($start, $end, $part) = $this->processValueTypes($value);

            return [
                'start' => $start,
                'end'   => $end,
                'type'  => $type,
                'part'  => $part
            ];
        }
        if (null === $value || ($value['value']['start'] === null && $value['value']['end'] === null)) {
            list($start, $end) = $this->dateHelper->getDateTimeInterval('P1M');
        } else {
            list($start, $end, $type) = $this->getPeriodValues($value);
            $start = $start instanceof \DateTime ? $start : $this->dateCompiler->compile($start);
            $end   = $end instanceof \DateTime ? $end : $this->dateCompiler->compile($end);
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

        return [
            'start' => $start,
            'end'   => $end,
            'type'  => $type,
            'part'  => $part
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getViewValue($value)
    {
        $start = $value['start'] instanceof \DateTime
            ? $value['start']
            : $this->dateCompiler->compile($value['start']);

        $end = $value['end'] instanceof \DateTime
            ? $value['end']
            : $this->dateCompiler->compile($value['end']);

        if (isset($value['part']) && $value['part'] === DateModifierInterface::PART_ALL_TIME) {
            return $this->translator->trans('oro.dashboard.widget.filter.date_range.all_time');
        }
        if ($value['type'] === AbstractDateFilterType::TYPE_MORE_THAN) {
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
    protected function getPeriodValues($value)
    {
        $startValue = $value['value']['start'];
        $endValue   = $value['value']['end'];
        $type       = $value['type'];

        if ($type === AbstractDateFilterType::TYPE_LESS_THAN
            || ($type === AbstractDateFilterType::TYPE_BETWEEN && $startValue === null)
        ) {
            $startValue = new \DateTime(self::MIN_DATE, new \DateTimeZone('UTC'));
            $type       = AbstractDateFilterType::TYPE_LESS_THAN;
        }

        if ($type === AbstractDateFilterType::TYPE_MORE_THAN
            || ($type === AbstractDateFilterType::TYPE_BETWEEN && $endValue === null)
        ) {
            $endValue = new \DateTime('now', new \DateTimeZone('UTC'));
            $type     = AbstractDateFilterType::TYPE_MORE_THAN;
        }

        return [$startValue, $endValue, $type];
    }

    /**
     * @param array $value
     *
     * @return array
     */
    protected function processValueTypes(array $value)
    {
        $start = $end = $part = null;
        if (array_key_exists($value['type'], static::$valueTypesStartVarsMap)) {
            /** @var \Carbon\Carbon $start */
            $start  = $this->dateCompiler->compile(
                sprintf('{{%s}}', static::$valueTypesStartVarsMap[$value['type']][0])
            );
            $end    = clone $start;
            $modify = static::$valueTypesStartVarsMap[$value['type']][1];
            if ($modify) {
                $end->modify($modify);
            }
            $start->setTime(0, 0, 0);
            $end->setTime(23, 59, 59);
        }
        if ($value['type'] === AbstractDateFilterType::TYPE_ALL_TIME) {
            $part = DateModifierInterface::PART_ALL_TIME;
        }

        return [$start, $end, $part];
    }
}
