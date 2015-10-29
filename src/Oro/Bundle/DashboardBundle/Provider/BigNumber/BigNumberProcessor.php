<?php

namespace Oro\Bundle\DashboardBundle\Provider\BigNumber;

use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

class BigNumberProcessor
{
    /** @var BigNumberFormatter */
    protected $bigNumberFormatter;

    /** @var BigNumberDateHelper */
    protected $dateHelper;

    /** @var object[] */
    protected $valueProviders = [];

    /**
     * @param BigNumberFormatter  $bigNumberFormatter
     * @param BigNumberDateHelper $dateHelper
     */
    public function __construct(
        BigNumberFormatter $bigNumberFormatter,
        BigNumberDateHelper $dateHelper
    ) {
        $this->bigNumberFormatter = $bigNumberFormatter;
        $this->dateHelper         = $dateHelper;
    }

    /**
     * @param WidgetOptionBag $widgetOptions
     * @param                 $providerAlias
     * @param                 $getterName
     * @param                 $dataType
     * @param bool            $lessIsBetter
     * @param bool            $lastWeek
     * @return array
     */
    public function getBigNumberValues(
        WidgetOptionBag $widgetOptions,
        $providerAlias,
        $getterName,
        $dataType,
        $lessIsBetter = false,
        $lastWeek = false
    ) {
        $getter           = $this->getGetter($providerAlias, $getterName);
        $lessIsBetter     = (bool) $lessIsBetter;
        $dateRange        = $lastWeek ? $this->dateHelper->getLastWeekPeriod() : $widgetOptions->get('dateRange');
        $value            = call_user_func($getter, $dateRange);
        $previousInterval = $widgetOptions->get('usePreviousInterval', []);
        $previousData = [];

        if (count($previousInterval)) {
            if ($lastWeek) {
                $previousInterval = $this->dateHelper->getLastWeekPeriod(-1);
            }

            $previousData['value']        = call_user_func($getter, $previousInterval);
            $previousData['dateRange']    = $previousInterval;
            $previousData['lessIsBetter'] = $lessIsBetter;
        }

        return $this->bigNumberFormatter->formatResult($value, $dataType, $previousData);
    }

    /**
     * @param string $providerAlias
     * @param string $getterName
     *
     * @return callable
     * @throws \LogicException
     */
    protected function getGetter($providerAlias, $getterName)
    {
        if (!isset($this->valueProviders[$providerAlias])) {
            throw new \LogicException(sprintf('BigNumber provider "%s" was not found', $providerAlias));
        }

        $callback = [$this->valueProviders[$providerAlias], $getterName];

        if (is_callable($callback)) {
            return $callback;
        }

        throw new \LogicException(
            sprintf('Getter "%s" for BigNumber provider "%s" was not found', $getterName, $providerAlias)
        );
    }

    /**
     * @param object $provider
     * @param string $alias
     */
    public function addValueProvider($provider, $alias)
    {
        $this->valueProviders[$alias] = $provider;
    }
}
