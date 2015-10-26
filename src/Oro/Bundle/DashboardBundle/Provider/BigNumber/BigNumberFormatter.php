<?php

namespace Oro\Bundle\DashboardBundle\Provider\BigNumber;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

class BigNumberFormatter
{
    /** @var NumberFormatter */
    protected $numberFormatter;

    /**
     * @param NumberFormatter $numberFormatter
     */
    public function __construct(NumberFormatter $numberFormatter)
    {
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * @param float  $value
     * @param string $type
     * @param bool   $isDeviant
     *
     * @return string
     */
    public function formatValue($value, $type = '', $isDeviant = false)
    {
        $sign = null;

        if ($isDeviant && $value !== 0) {
            $sign  = $value > 0 ? '+' : '&minus;';
            $value = abs($value);
        }

        switch ($type) {
            case 'currency':
                $value = $this->numberFormatter->formatCurrency($value);
                break;
            case 'percent':
                $precision = $isDeviant ? 2 : 4;
                $value = round($value, $precision);
                $value = $this->numberFormatter->formatPercent($value);
                break;
            default:
                $value = $this->numberFormatter->formatDecimal($value);
        }

        return !is_null($sign) ? sprintf('%s%s', $sign, $value) : $value;
    }

    /**
     * Formats BigNumber result for view
     *
     * @param int    $value
     * @param string $dataType
     * @param array  $previousData
     *
     * @return array
     */
    public function formatResult(
        $value,
        $dataType,
        array $previousData = []
    ) {
        $result = ['value' => $this->formatValue($value, $dataType)];

        if (count($previousData)) {
            $pastResult = $previousData['value'];
            $previousInterval = $previousData['dateRange'];
            $deviation = $value - $pastResult;
            $result['deviation'] = '';

            // Check that deviation won't be formatted as zero
            if (round($deviation, 2) != 0) {
                $result['deviation'] = $this->formatValue($deviation, $dataType, true);
                $result['isPositive'] = ($previousData['lessIsBetter'] xor ($deviation > 0));

                if ($pastResult != 0 && $dataType !== 'percent') {
                    $deviationPercent = $deviation / $pastResult;
                    $result['deviation'] .= sprintf(
                        ' (%s)',
                        $this->formatValue($deviationPercent, 'percent', true)
                    );
                }
            }

            $result['previousRange'] = $previousInterval;
        }

        return $result;
    }
}
