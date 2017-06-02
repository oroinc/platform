<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\AbstractImportHandler;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

abstract class PreImportMessageProcessorAbstract implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobRunner
     */
    protected $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DependentJobService
     */
    protected $dependentJob;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var AbstractImportHandler
     */
    protected $importHandler;

    /**
     * @var WriterChain
     */
    protected $writerChain;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var integer
     */
    protected $batchSize;

    /**
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     * @param DependentJobService $dependentJob
     * @param FileManager $fileManager
     * @param AbstractImportHandler $importHandler
     * @param WriterChain $writerChain
     * @param integer $batchSize
     */
    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        DependentJobService $dependentJob,
        FileManager $fileManager,
        AbstractImportHandler $importHandler,
        WriterChain $writerChain,
        $batchSize
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->dependentJob = $dependentJob;
        $this->fileManager = $fileManager;
        $this->importHandler = $importHandler;
        $this->writerChain = $writerChain;
        $this->batchSize = $batchSize;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param array $body
     *
     * @return array|bool
     */
    abstract protected function validateMessageBody($body);

    /**
     * @param array $body
     * @param array $files
     *
     * @return bool
     */
    abstract protected function processJob($parentMessageId, $body, $files);

    /**
     * @param array $body
     * @param string $error
     */
    abstract protected function sendErrorNotification(array $body, $error);

    /**
     * @param array $body
     *
     * @return array|bool
     */
    protected function getFiles($body)
    {
        $format = pathinfo($body['originFileName'], PATHINFO_EXTENSION);
        $writer = $this->writerChain->getWriter($format);

        if (!$writer instanceof FileStreamWriter) {
            $reflect = new \ReflectionClass($this);
            $this->logger->warning(
                sprintf('[%s] Not supported format: "%s", using default', $reflect->getShortName(), $format),
                ['body' => $body]
            );
            $writer = $this->writerChain->getWriter('csv');
        }

        try {
            $filePath = $this->fileManager->writeToTmpLocalStorage($body['fileName']);
            $this->importHandler->setImportingFileName($filePath);
            $files = $this->importHandler->splitImportFile(
                $body['jobName'],
                $body['process'],
                $writer
            );
        } catch (\Exception $e) {
            $this->sendErrorNotification($body, $e->getMessage());

            return false;
        } catch (\Throwable $e) {
            $this->sendErrorNotification($body, $e->getMessage());

            return false;
        }

        return $files;
    }


    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        $body = $this->validateMessageBody($body);

        if (! $body) {
            return self::REJECT;
        }

        $files = $this->getFiles($body, $message);

        if (!$files) {
            return self::REJECT;
        }

        $parentMessageId = $message->getMessageId();

        $result = $this->processJob($parentMessageId, $body, $files);

        return $result ? self::ACK : self::REJECT;
    }
}
