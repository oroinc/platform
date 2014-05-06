<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Reader;

use Oro\Bundle\ImportExportBundle\Reader\EntityReader;

class EntityReaderByIdAdapterTest extends EntityReader
{
    public function setSomeSourceIterator(\Iterator $iterator)
    {
        $this->setSourceIterator($iterator);
    }
}
