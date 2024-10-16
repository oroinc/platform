<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Writer;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;

class DummyProcessor implements ProcessorInterface
{
    #[\Override]
    public function process($item)
    {
        return $item;
    }
}
