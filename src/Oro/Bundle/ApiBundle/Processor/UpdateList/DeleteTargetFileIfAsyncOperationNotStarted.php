<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Deletes a file to which the request data was stored
 * in case a message to start the asynchronous operation was not sent to the message queue.
 */
class DeleteTargetFileIfAsyncOperationNotStarted implements ProcessorInterface
{
    private FileManager $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        $fileName = $context->getTargetFileName();
        if (!$fileName) {
            return;
        }

        if ($context->isProcessed(StartAsyncOperation::OPERATION_NAME)) {
            return;
        }

        if ($this->fileManager->hasFile($fileName)) {
            $this->fileManager->deleteFile($fileName);
        }
        $context->setTargetFileName(null);
    }
}
