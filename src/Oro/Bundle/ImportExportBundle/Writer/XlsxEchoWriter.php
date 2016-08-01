<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Writes XLSX to PHP output stream but creates a tmp file before
 */
class XlsxEchoWriter extends XlsxFileStreamWriter
{
	/**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->filePath = 'php://output';

        parent::setImportExportContext($context);
    }
}
