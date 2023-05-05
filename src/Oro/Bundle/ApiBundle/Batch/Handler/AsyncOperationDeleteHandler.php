<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Exception\DeleteAsyncOperationException;
use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandler;
use Oro\Bundle\GaufretteBundle\FileManager;
use Psr\Log\LoggerInterface;

/**
 * The delete handler for the AsyncOperation entity that removes the operation related files as well.
 */
class AsyncOperationDeleteHandler extends AbstractEntityDeleteHandler
{
    private FileNameProvider $fileNameProvider;
    private FileManager $fileManager;
    private LoggerInterface $logger;

    public function __construct(FileNameProvider $fileNameProvider, FileManager $fileManager, LoggerInterface $logger)
    {
        $this->fileNameProvider = $fileNameProvider;
        $this->fileManager = $fileManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function isDeleteGranted($entity): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function deleteWithoutFlush($entity, array $options): void
    {
        /** @var AsyncOperation $entity */
        if (!$this->deleteAsyncOperationFiles($entity->getId())) {
            throw new DeleteAsyncOperationException(
                'Failed to delete all files related to the asynchronous operation.'
            );
        }
        parent::deleteWithoutFlush($entity, $options);
    }

    private function deleteAsyncOperationFiles(int $operationId): bool
    {
        $fileNamePrefix = $this->fileNameProvider->getFilePrefix($operationId);

        $success = true;
        $fileNames = [];
        try {
            $fileNames = $this->fileManager->findFiles($fileNamePrefix);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('The finding of files for entity %d is failed.', $operationId),
                ['exception' => $e]
            );

            $success = false;
        }

        foreach ($fileNames as $fileName) {
            try {
                $this->fileManager->deleteFile($fileName);
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf('The deletion of the file "%s" failed.', $fileName),
                    ['exception' => $e]
                );

                $success = false;
            }
        }

        return $success;
    }
}
