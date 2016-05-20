<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use \Datetime;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

use Oro\Bundle\DashboardBundle\Helper\DateHelper;

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
        if (is_null($value)
            || ($value['value']['start'] === null && $value['value']['end'] === null)
        ) {
            list($start, $end) = $this->dateHelper->getDateTimeInterval('P1M');

            $type  = AbstractDateFilterType::TYPE_BETWEEN;
        } else {
            list($startValue, $endValue, $type) = $this->getPeriodValues($value);

            $start = $startValue instanceof DateTime ? $startValue : $this->dateCompiler->compile($startValue);
            $end = $endValue instanceof DateTime ? $endValue : $this->dateCompiler->compile($endValue);
            //Swap start and end dates if end date is behind start date
            if ($start > $end) {
                $e = $end;
                $end = $start;
                $start = $e;
            }
            $start->setTime(0, 0, 0);
            $end->setTime(23, 59, 59);
        }

        return [
            'start' => $start,
            'end'   => $end,
            'type'  => $type
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getViewValue($value)
    {
        switch ($value['type']) {
            case AbstractDateFilterType::TYPE_MORE_THAN:
                return sprintf(
                    '%s %s',
                    $this->translator->trans('oro.filter.form.label_date_type_more_than'),
                    $this->formatter->formatDate($value['start'])
                );
            case AbstractDateFilterType::TYPE_LESS_THAN:
                return sprintf(
                    '%s %s',
                    $this->translator->trans('oro.filter.form.label_date_type_less_than'),
                    $this->formatter->formatDate($value['end'])
                );
        }

        return sprintf(
            '%s - %s',
            $this->formatter->formatDate($value['start']),
            $this->formatter->formatDate($value['end'])
        );
    }

    /**
     * @param array $value
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
            $startValue = new DateTime(self::MIN_DATE, new \DateTimeZone('UTC'));
            $type       = AbstractDateFilterType::TYPE_LESS_THAN;
        }

        if ($type === AbstractDateFilterType::TYPE_MORE_THAN
            || ($type === AbstractDateFilterType::TYPE_BETWEEN && $endValue === null)
        ) {
            $endValue = new DateTime('now', new \DateTimeZone('UTC'));
            $type     = AbstractDateFilterType::TYPE_MORE_THAN;
        }

        return [$startValue, $endValue, $type];
    }
}
