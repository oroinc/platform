<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Oro\Bundle\ImportExportBundle\Reader\EntityReader;

class EntityReaderTestAdapter extends EntityReader
{
    public function setSourceIterator(\Iterator $iterator)
    {
        $this->sourceIterator = $iterator;
    }
}
