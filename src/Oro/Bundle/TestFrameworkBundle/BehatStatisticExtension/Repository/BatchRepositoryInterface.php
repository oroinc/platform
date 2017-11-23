<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\StatisticModelInterface;

interface BatchRepositoryInterface
{
    /**
     * Add Model to collection
     * @param StatisticModelInterface $model
     */
    public function add(StatisticModelInterface $model);

    /**
     * Insert records in persistent layer
     * @return void
     */
    public function flush();
}
