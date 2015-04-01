<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverter;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class FilterDateTimeRangeConverter implements ConfigValueConverter
{
    /** @var DateTimeFormatter */
    protected $formatter;

    public function __construct(DateTimeFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @inheritdoc
     */
    public function getConvertedValue($value = null)
    {
        if (is_null($value)) {
            $end = new \DateTime('now', new \DateTimeZone('UTC'));
            $start = clone $end;
            $start = $start->sub(new \DateInterval('P1M'));
        } else {
            $start = $value['value']['start'];
            $end = $value['value']['end'];
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
