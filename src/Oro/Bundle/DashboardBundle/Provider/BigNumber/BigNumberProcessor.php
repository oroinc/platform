<?php

namespace Oro\Bundle\DashboardBundle\Provider\BigNumber;

use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Psr\Container\ContainerInterface;

/**
 * Provides a way to process big numbers.
 */
class BigNumberProcessor
{
    /** @var ContainerInterface */
    private $valueProviders;

    /** @var BigNumberFormatter */
    private $bigNumberFormatter;

    /** @var BigNumberDateHelper */
    private $dateHelper;

    public function __construct(
        ContainerInterface $valueProviders,
        BigNumberFormatter $bigNumberFormatter,
        BigNumberDateHelper $dateHelper
    ) {
        $this->valueProviders = $valueProviders;
        $this->bigNumberFormatter = $bigNumberFormatter;
        $this->dateHelper = $dateHelper;
    }

    /**
     * @param WidgetOptionBag $widgetOptions
     * @param string          $providerAlias
     * @param string          $getterName
     * @param string          $dataType
     * @param bool            $lessIsBetter
     * @param bool            $lastWeek
     * @param string          $comparable
     *
     * @return array
     */
    public function getBigNumberValues(
        WidgetOptionBag $widgetOptions,
        string $providerAlias,
        $getterName,
        $dataType,
        $lessIsBetter = false,
        $lastWeek = false,
        $comparable = 'true'
    ) {
        $getter           = $this->getGetter($providerAlias, $getterName);
        $lessIsBetter     = (bool)$lessIsBetter;
        $dateRange        = $lastWeek ? $this->dateHelper->getLastWeekPeriod() : $widgetOptions->get('dateRange');
        $value            = call_user_func($getter, $dateRange, $widgetOptions);
        $previousInterval = $widgetOptions->get('usePreviousInterval', []);
        $previousData     = [];
        $comparable       = $comparable == 'true' ? true : false;

        if (count($previousInterval)) {
            if ($comparable) {
                if ($lastWeek) {
                    $previousInterval = $this->dateHelper->getLastWeekPeriod(-1);
                }

                $previousData['value']        = call_user_func($getter, $previousInterval, $widgetOptions);
                $previousData['dateRange']    = $previousInterval;
                $previousData['lessIsBetter'] = $lessIsBetter;
            }

            $previousData['comparable'] = $comparable;
        }

        return $this->bigNumberFormatter->formatResult($value, $dataType, $previousData);
    }

    /**
     * @param string $providerAlias
     * @param string $getterName
     *
     * @return callable
     */
    private function getGetter(string $providerAlias, $getterName)
    {
        if (!$this->valueProviders->has($providerAlias)) {
            throw new \LogicException(sprintf('BigNumber provider "%s" was not found', $providerAlias));
        }

        $callback = [$this->valueProviders->get($providerAlias), $getterName];

        if (is_callable($callback)) {
            return $callback;
        }

        throw new \LogicException(
            sprintf('Getter "%s" for BigNumber provider "%s" was not found', $getterName, $providerAlias)
        );
    }
}
