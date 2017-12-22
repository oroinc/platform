<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

use Doctrine\Common\Persistence\ObjectRepository;

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
