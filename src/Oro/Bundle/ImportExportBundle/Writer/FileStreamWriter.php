<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;

/**
 * Base file stream batch job writer.
 */
abstract class FileStreamWriter implements ItemWriterInterface, ContextAwareInterface, ClosableInterface
{
    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var array
     */
    protected $header;
}
