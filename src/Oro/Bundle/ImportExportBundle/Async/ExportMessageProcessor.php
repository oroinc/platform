<?php
namespace Oro\Bundle\ImportExportBundle\Async;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class ExportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ExportHandler
     */
    private $exportHandler;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ExportHandler $exportHandler
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger
     */
    public function __construct(ExportHandler $exportHandler, JobRunner $jobRunner, LoggerInterface $logger)
    {
        $this->exportHandler = $exportHandler;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'jobName' => null,
            'processorAlias' => null,
            'exportType' => ProcessorRegistry::TYPE_EXPORT,
            'outputFormat' => 'csv',
            'outputFilePrefix' => null,
            'options' => [],
        ], $body);

        if (! isset($body['jobName'], $body['processorAlias'])) {
            $this->logger->critical(
                sprintf('[ExportMessageProcessor] Got invalid message: "%s"', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $jobUniqueName = Topics::EXPORT . '_' . $body['processorAlias'];

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobUniqueName,
            function () use ($body) {
                $exportResult = $this->exportHandler->handleExport($body);

                return $exportResult->isSuccessful();
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT];
    }
}
