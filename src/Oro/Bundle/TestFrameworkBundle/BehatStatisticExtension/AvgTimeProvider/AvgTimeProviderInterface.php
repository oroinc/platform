<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

interface AvgTimeProviderInterface
{
    /**
     * @param string|int $id
     * @return int|null
     */
    public function getAverageTimeById($id);

    /**
     * @return int
     */
    public function getAverageTime();
}
