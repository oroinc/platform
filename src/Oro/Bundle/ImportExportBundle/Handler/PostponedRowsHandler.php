<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Writes all postponed entries to a file and creates a retry job for processing.
 * Sets the number of attempts and delay time
 */
class PostponedRowsHandler
{
    const MAX_ATTEMPTS = 30;

    const DELAY_SECONDS = 5;

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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param FileManager              $fileManager
     * @param MessageProducerInterface $messageProducer
     * @param WriterChain              $writerChain
     * @param TranslatorInterface      $translator
     */
    public function __construct(
        FileManager $fileManager,
        MessageProducerInterface $messageProducer,
        WriterChain $writerChain,
        TranslatorInterface $translator
    ) {
        $this->fileManager = $fileManager;
        $this->messageProducer = $messageProducer;
        $this->writerChain = $writerChain;
        $this->translator = $translator;
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
        if (!$writer instanceof FileStreamWriter) {
            $writer = $this->writerChain->getWriter('csv');
        }
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
     * @param array     $result
     */
    public function postpone(JobRunner $jobRunner, Job $currentJob, $fileName, array $body, array &$result)
    {
        $attempts = isset($body['attempts']) ? (int)$body['attempts'] + 1 : 1;

        if ($attempts > self::MAX_ATTEMPTS) {
            if (array_key_exists('postponedRows', $result) && !empty($result['postponedRows'])) {
                $result['errors'][] = $this->translator->trans(
                    'oro.importexport.import.postponed_rows',
                    ['%postponedRows%' => count($result['postponedRows'])]
                );
                $result['counts']['errors']++;
            }
            return;
        }

        $jobRunner->createDelayed(
            sprintf('%s:postponed:%s', $currentJob->getRootJob()->getName(), $attempts),
            function (JobRunner $jobRunner, Job $child) use ($body, $fileName, $attempts) {
                $body = array_merge($body, [
                    'jobId' => $child->getId(),
                    'attempts' => $attempts,
                    'fileName' => $fileName,
                ]);

                if (array_key_exists('options', $body) && !array_key_exists('incremented_read', $body['options'])) {
                    $body['options']['incremented_read'] = false;
                }
                $message = new Message();
                $message->setDelay(static::DELAY_SECONDS);
                $message->setBody($body);
                $this->messageProducer->send(Topics::HTTP_IMPORT, $message);
            }
        );
    }
}
