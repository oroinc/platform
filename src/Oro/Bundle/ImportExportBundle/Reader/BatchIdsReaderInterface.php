<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

interface BatchIdsReaderInterface
{
    /**
     * Get ids, it can ids or number of rows, or smth else that can help with identification the read elements.
     *
     * @param string $sourceName
     * @param array $options
     * @return array
     */
    public function getIds($sourceName, array $options = []);
}
