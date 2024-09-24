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
    #[\Override]
    protected function open()
    {
        return fopen($this->filePath, 'r+');
    }

    #[\Override]
    public function setImportExportContext(ContextInterface $context)
    {
        $this->filePath = 'php://output';

        parent::setImportExportContext($context);
    }
}
