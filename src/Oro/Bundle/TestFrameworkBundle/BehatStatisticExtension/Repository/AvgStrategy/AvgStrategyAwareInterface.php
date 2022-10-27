<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\AvgStrategy;

interface AvgStrategyAwareInterface
{
    public function setAvgStrategy(AvgStrategyInterface $strategy);
}
