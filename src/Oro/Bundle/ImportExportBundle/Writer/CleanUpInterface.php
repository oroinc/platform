<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

interface CleanUpInterface
{
    /**
     * Remove outdated records.
     *
     * @param array $item
     */
    public function cleanUp(array $item);
}
