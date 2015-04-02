<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use \Datetime;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverter;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class FilterDateTimeRangeConverter implements ConfigValueConverter
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
    public function getConvertedValue($value = null)
    {
        if (is_null($value)) {
            $end = new DateTime('now', new \DateTimeZone('UTC'));
            $start = clone $end;
            $start = $start->sub(new \DateInterval('P1M'));
        } else {
            $startValue = $value['value']['start'];
            $endValue = $value['value']['end'];

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
            $this->formatter->format($value['start']),
            $this->formatter->format($value['end'])
        );
    }
}
