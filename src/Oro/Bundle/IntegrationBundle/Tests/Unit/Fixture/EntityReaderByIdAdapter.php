<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\ImportExportBundle\Reader\EntityReader;

class EntityReaderByIdAdapter extends EntityReader
{
    public function setSomeSourceIterator(\Iterator $iterator)
    {
        $this->setSourceIterator($iterator);
    }
}
