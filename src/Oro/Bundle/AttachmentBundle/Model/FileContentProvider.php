<?php

namespace Oro\Bundle\AttachmentBundle\Model;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\SoapBundle\Model\BinaryDataProviderInterface;

class FileContentProvider implements BinaryDataProviderInterface
{
    /** @var string */
    protected $fileName;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /**
     * @param string            $fileName
     * @param AttachmentManager $attachmentManager
     */
    public function __construct($fileName, AttachmentManager $attachmentManager)
    {
        $this->fileName          = $fileName;
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->attachmentManager->getContent($this->fileName);
    }
}
