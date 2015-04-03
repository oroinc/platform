<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use \Datetime;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class FilterDateTimeRangeConverter extends ConfigValueConverterAbstract
{
    /** @var DateTimeFormatter */
    protected $formatter;

    /** @var Compiler */
    protected $dateCompiler;

    /**
     * @param DateTimeFormatter $formatter
     * @param Compiler          $dateCompiler
     */
    public function __construct(DateTimeFormatter $formatter, Compiler $dateCompiler)
    {
        $this->formatter = $formatter;
        $this->dateCompiler = $dateCompiler;
    }

    /**
     * @inheritdoc
     */
    public function getConvertedValue(array $widgetConfig, $value = null)
    {
        if (is_null($value)
            || ($value['value']['start'] === null && $value['value']['end'] === null)
        ) {
            $end = new DateTime('now', new \DateTimeZone('UTC'));
            $start = clone $end;
            $start = $start->sub(new \DateInterval('P1M'));
        } else {
            $startValue = $value['value']['start'];
            $endValue = $value['value']['end'];

            switch ($value['type']) {
                case AbstractDateFilterType::TYPE_LESS_THAN:
                    $startValue = new DateTime('2000-01-01', new \DateTimeZone('UTC'));
                    break;
                case AbstractDateFilterType::TYPE_MORE_THAN:
                    $endValue = new DateTime('now', new \DateTimeZone('UTC'));
                    break;
            }

            $start = $startValue instanceof DateTime ? $startValue : $this->dateCompiler->compile($startValue);
            $end = $endValue instanceof DateTime ? $endValue : $this->dateCompiler->compile($endValue);
        }

        return [
            'start' => $start,
            'end' => $end
        ];
    }

    /**
     * @inheritdoc
     */
    public function getViewValue($value)
    {
        return sprintf(
            '%s - %s',
            $this->formatter->formatDate($value['start']),
            $this->formatter->formatDate($value['end'])
        );
    }
}
