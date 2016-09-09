<?php

namespace Oro\Bundle\AttachmentBundle\Model;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\SoapBundle\Model\BinaryDataProviderInterface;

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

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->fileManager->getContent($this->fileName);
    }
}
