<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\Repository;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\StatisticModelInterface;

interface StatisticRepositoryInterface
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
