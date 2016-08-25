<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;

abstract class FileStreamWriter implements ItemWriterInterface, ContextAwareInterface, ClosableInterface
{
    /**
     * @var string
     */
    protected $filePath;
}
