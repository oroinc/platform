<?php

namespace Oro\Bundle\AttachmentBundle\Model;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\SoapBundle\Model\BinaryDataProviderInterface;

/**
 * Provides binary file content for SOAP operations.
 *
 * This provider implements the {@see BinaryDataProviderInterface} to supply file content
 * for SOAP-based operations. It acts as a bridge between the file management system
 * and SOAP services, allowing file content to be retrieved and transmitted through
 * SOAP endpoints. The provider encapsulates a file name and file manager instance
 * to facilitate content retrieval on demand.
 */
class FileContentProvider implements BinaryDataProviderInterface
{
    /** @var string */
    protected $fileName;

    /** @var FileManager */
    protected $fileManager;

    /**
     * @param string      $fileName
     * @param FileManager $fileManager
     */
    public function __construct($fileName, FileManager $fileManager)
    {
        $this->fileName    = $fileName;
        $this->fileManager = $fileManager;
    }

    #[\Override]
    public function getData()
    {
        return $this->fileManager->getContent($this->fileName);
    }
}
