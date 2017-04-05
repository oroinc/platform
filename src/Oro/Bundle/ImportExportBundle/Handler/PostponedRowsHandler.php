<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\JobRunner;

class PostponedRowsHandler
{
    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var WriterChain
     */
    private $writerChain;

    /**
     * @param FileManager              $fileManager
     * @param MessageProducerInterface $messageProducer
     * @param WriterChain              $writerChain
     */
    public function __construct(
        FileManager $fileManager,
        MessageProducerInterface $messageProducer,
        WriterChain $writerChain
    ) {
        $this->fileManager = $fileManager;
        $this->messageProducer = $messageProducer;
        $this->writerChain = $writerChain;
    }

    /**
     * @param array  $rows
     * @param string $originalFileName
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function writeRowsToFile(array $rows, $originalFileName)
    {
        if (count($rows) === 0) {
            throw new \InvalidArgumentException('Cannot save empty rows');
        }

        // similar approach in BatchFileManager::writeBatch
        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        $fileName = FileManager::generateUniqueFileName($extension);
        $filePath = FileManager::generateTmpFilePath($fileName);

        $writerContext = new Context(['filePath' => $filePath]);
        /** @var FileStreamWriter $writer */
        $writer = $this->writerChain->getWriter($extension);
        $writer->setImportExportContext($writerContext);
        $writer->write($rows);
        $writer->close();

        $this->fileManager->writeFileToStorage($filePath, $fileName);
        @unlink($filePath);

        return $fileName;
    }

    /**
     * @param JobRunner $jobRunner
     * @param Job       $currentJob
     * @param string    $fileName
     * @param array     $body
     */
    public function postpone(JobRunner $jobRunner, Job $currentJob, $fileName, array $body)
    {
        $attempts = isset($body['attempts']) ? (int)$body['attempts'] + 1 : 1;

        // @todo refactor in BB-8550
        if ($attempts >= 3) {
            return;
        }

        $jobRunner->createDelayed(
            sprintf('%s:postponed:%s', $currentJob->getRootJob()->getName(), $attempts),
            function (JobRunner $jobRunner, Job $child) use ($body, $fileName, $attempts) {
                $body['fileName'] = $fileName;
                $this->messageProducer->send(
                    Topics::HTTP_IMPORT,
                    array_merge($body, [
                        'jobId' => $child->getId(),
                        'attempts' => $attempts,
                    ])
                );
            }
        );
    }
}
