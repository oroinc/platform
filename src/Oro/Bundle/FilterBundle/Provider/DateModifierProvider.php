<?php

namespace Oro\Bundle\FilterBundle\Provider;

class DateModifierProvider implements DateModifierInterface
{
    /** @var array */
    protected $variableLabelsMap
        = [
            self::VAR_NOW          => 'now',
            self::VAR_TODAY        => 'today',
            self::VAR_SOW          => 'sow',
            self::VAR_SOM          => 'som',
            self::VAR_SOQ          => 'soq',
            self::VAR_SOY          => 'soy',
            self::VAR_THIS_DAY     => 'this_day',
            self::VAR_THIS_WEEK    => 'this_week',
            self::VAR_THIS_MONTH   => 'this_month',
            self::VAR_FMQ          => 'this_fmq',
            self::VAR_THIS_QUARTER => 'this_quarter',
            self::VAR_FDQ          => 'this_fdq',
            self::VAR_THIS_YEAR    => 'this_year',
        ];

    /** @var array */
    protected $varMap
        = [
            self::PART_VALUE   => [
                self::VAR_NOW,
                self::VAR_TODAY,
                self::VAR_SOW,
                self::VAR_SOM,
                self::VAR_SOQ,
                self::VAR_SOY,
            ],
            self::PART_DOW     => [
                self::VAR_THIS_DAY,
                self::VAR_FDQ,
            ],
            self::PART_WEEK    => [
                self::VAR_THIS_WEEK
            ],
            self::PART_DAY     => [
                self::VAR_THIS_DAY,
                self::VAR_FDQ
            ],
            self::PART_MONTH   => [
                self::VAR_THIS_MONTH,
                self::VAR_FMQ
            ],
            self::PART_QUARTER => [
                self::VAR_THIS_QUARTER
            ],
            self::PART_DOY     => [
                self::VAR_THIS_DAY,
                self::VAR_FDQ
            ],
            self::PART_YEAR    => [
                self::VAR_THIS_YEAR
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
        $self   = $this;

        foreach ($this->varMap as $part => $vars) {
            $result[$part] = array_combine(
                array_values($vars),
                array_map(
                    function ($item) use ($self) {
                        return $self->getVariableKey($item);
                    },
                    $vars
                )
            );
        }

        return $result;
    }

    /**
     * Get variable
     *
     * @param string $variable
     *
     * @return string
     */
    public function getVariableKey($variable)
    {
        return self::LABEL_VAR_PREFIX . $this->variableLabelsMap[$variable];
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
