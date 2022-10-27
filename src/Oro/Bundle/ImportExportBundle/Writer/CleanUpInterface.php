<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

interface CleanUpInterface
{
    /**
     * Remove outdated records.
     */
    public function cleanUp(array $item);
}
