<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

class NullProcessor implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        return $item;
    }
}
