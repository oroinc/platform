<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Oro\Bundle\ImportExportBundle\Reader\EntityReader;

class EntityReaderTestAdapter extends EntityReader
{
    public function setSomeSourceIterator(\Iterator $iterator)
    {
        $this->setSourceIterator($iterator);
    }
}
