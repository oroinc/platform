<?php

namespace Oro\Bundle\ImportExportBundle\Context;

interface BatchContextInterface
{
    /**
     * @return int
     */
    public function getBatchSize();

    /**
     * @return int
     */
    public function getBatchNumber();
}
