<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\AvgStrategy;

interface AvgStrategyAwareInterface
{
    /**
     * @param AvgStrategyInterface $strategy
     */
    public function setAvgStrategy(AvgStrategyInterface $strategy);
}
