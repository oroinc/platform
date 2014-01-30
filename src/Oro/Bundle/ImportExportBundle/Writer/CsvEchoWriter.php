<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Writes CSV to PHP output stream
 */
class CsvEchoWriter extends CsvFileStreamWriter
{
    /**
     * Open file.
     *
     * @return resource
     */
    protected function open()
    {
        return fopen($this->filePath, 'r+');
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->filePath = 'php://output';

        parent::setImportExportContext($context);
    }
}
