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
        AbstractDateFilterType::TYPE_TODAY        => DateModifierInterface::VAR_TODAY,
        AbstractDateFilterType::TYPE_THIS_WEEK    => DateModifierInterface::VAR_SOW,
        AbstractDateFilterType::TYPE_THIS_MONTH   => DateModifierInterface::VAR_SOM,
        AbstractDateFilterType::TYPE_THIS_QUARTER => DateModifierInterface::VAR_SOQ,
        AbstractDateFilterType::TYPE_THIS_YEAR    => DateModifierInterface::VAR_SOY,
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
        $start = $end = $part = $type = $valueType = null;
        $part  = DateModifierInterface::PART_VALUE;
        if (in_array($value['type'], AbstractDateFilterType::$valueTypes)) {
            $valueType = $value['type'];
            if (array_key_exists($value['type'], static::$valueTypesStartVarsMap)) {
                $start = sprintf('{{%s}}', static::$valueTypesStartVarsMap[$value['type']]);
                $type  = AbstractDateFilterType::TYPE_MORE_THAN;
            } elseif ($value['type'] === AbstractDateFilterType::TYPE_ALL_TIME) {
                $part = DateModifierInterface::PART_ALL_TIME;
            }
        } elseif (empty($value['value']['start']) && empty($value['value']['end'])) {
            list($start, $end) = $this->dateHelper->getDateTimeInterval('P1M');
            $type = AbstractDateFilterType::TYPE_BETWEEN;
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
            'start'      => $start,
            'end'        => $end,
            'type'       => $type,
            'value_type' => $valueType,
            'part'       => $part,
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

        $end   = $value['end'] instanceof \DateTime
            ? $value['end']
            : $this->dateCompiler->compile($value['end']);

        if ($value['part'] === DateModifierInterface::PART_ALL_TIME) {
            return $this->translator->trans('oro.dashboard.widget.filter.date_range.all_time');
        } elseif ($value['type'] === AbstractDateFilterType::TYPE_MORE_THAN) {
            return sprintf(
                '%s %s',
                $this->translator->trans('oro.filter.form.label_date_type_more_than'),
                $this->formatter->formatDate($start)
            );
        } elseif ($value['type'] === AbstractDateFilterType::TYPE_LESS_THAN) {
            return sprintf(
                '%s %s',
                $this->translator->trans('oro.filter.form.label_date_type_less_than'),
                $this->formatter->formatDate($end)
            );
        } else {
            return sprintf(
                '%s - %s',
                $this->formatter->formatDate($start),
                $this->formatter->formatDate($end)
            );
        }
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
}
