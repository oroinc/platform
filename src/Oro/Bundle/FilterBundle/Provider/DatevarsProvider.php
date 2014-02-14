<?php

namespace Oro\Bundle\FilterBundle\Provider;

class DatevarsProvider implements DatevariablesInterface
{
    protected $varMap = [
        self::VAR_NOW   => 'now',
        self::VAR_TODAY => 'today',
        self::VAR_SOW   => 'sow',
        self::VAR_SOM   => 'som',
        self::VAR_SOQ   => 'soq',
        self::VAR_SOY   => 'soy',

        self::VAR_THIS_DAY     => 'this_day',
        self::VAR_THIS_WEEK    => 'this_week',
        self::VAR_THIS_MONTH   => 'this_month',
        self::VAR_THIS_QUARTER => 'this_quarter',
        self::VAR_THIS_YEAR    => 'this_year',
        self::VAR_FDQ          => 'this_fdq',
        self::VAR_FMQ          => 'this_fmq',
    ];

    /**
     * @return array
     */
    public function getDateVariables()
    {
        $result = array_map(
            function ($item) {
                return self::LABEL_PREFIX . $item;
            },
            $this->varMap
        );

        return $result;
    }
}
