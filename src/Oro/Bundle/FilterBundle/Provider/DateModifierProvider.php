<?php

namespace Oro\Bundle\FilterBundle\Provider;

class DateModifierProvider implements DateModifierInterface
{
    /** @var array */
    protected $varMap = [
        self::PART_VALUE => [
            self::VAR_NOW   => 'now',
            self::VAR_TODAY => 'today',
            self::VAR_SOW   => 'sow',
            self::VAR_SOM   => 'som',
            self::VAR_SOQ   => 'soq',
            self::VAR_SOY   => 'soy',
        ],
        self::PART_DOW => [
            self::VAR_FDQ          => 'this_fdq',
        ],
        self::PART_WEEK => [
            self::VAR_THIS_WEEK    => 'this_week',
        ],
        self::PART_DAY => [
            self::VAR_THIS_DAY     => 'this_day',
            self::VAR_FDQ          => 'this_fdq',
        ],
        self::PART_MONTH => [
            self::VAR_THIS_MONTH   => 'this_month',
            self::VAR_FMQ          => 'this_fmq',
        ],
        self::PART_QUARTER => [
            self::VAR_THIS_QUARTER => 'this_quarter',
        ],
        self::PART_DOY => [
            self::VAR_FDQ          => 'this_fdq',
        ],
        self::PART_YEAR => [
            self::VAR_THIS_YEAR    => 'this_year',
        ],
    ];

    /**
     * Return date variables available for each date part
     * as associative array
     *
     * @return array
     */
    public function getDateVariables()
    {
        $result = [];

        foreach ($this->varMap as $part => $vars) {
            $result[$part] = array_map(
                function ($item) {
                    return self::LABEL_VAR_PREFIX . $item;
                },
                $vars
            );
        }

        return $result;
    }

    /**
     * Return date part associative array where key is the code and
     * value is translatable string
     *
     * @return array
     */
    public function getDateParts()
    {
        return [
            self::PART_VALUE   => 'oro.filter.form.label_date_part.' . self::PART_VALUE,
            self::PART_DOW     => 'oro.filter.form.label_date_part.' . self::PART_DOW,
            self::PART_WEEK    => 'oro.filter.form.label_date_part.' . self::PART_WEEK,
            self::PART_DAY     => 'oro.filter.form.label_date_part.' . self::PART_DAY,
            self::PART_MONTH   => 'oro.filter.form.label_date_part.' . self::PART_MONTH,
            self::PART_QUARTER => 'oro.filter.form.label_date_part.' . self::PART_QUARTER,
            self::PART_DOY     => 'oro.filter.form.label_date_part.' . self::PART_DOY,
            self::PART_YEAR    => 'oro.filter.form.label_date_part.' . self::PART_YEAR,
        ];
    }
}
