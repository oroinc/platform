<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\StatisticModelInterface;

interface BatchRepositoryInterface
{
    /**
     * Add Model to collection
     */
    public function add(StatisticModelInterface $model);

    /**
     * Insert records in persistent layer
     * @return void
     */
    public function flush();
}
