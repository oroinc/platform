<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Writes XLSX to PHP output stream but creates a tmp file before
 */
class XlsxEchoWriter extends XlsxFileStreamWriter
{
    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context): void
    {
        $this->filePath = 'php://output';

        parent::setImportExportContext($context);
    }
}
