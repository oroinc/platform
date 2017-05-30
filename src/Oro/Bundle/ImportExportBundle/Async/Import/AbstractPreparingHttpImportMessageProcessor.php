<?php
namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;

use Psr\Log\LoggerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\SplitterCsvFile;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

abstract class AbstractPreparingHttpImportMessageProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    PreparingHttpImportMessageProcessorInterface
{
    /**
     * @var HttpImportHandler
     */
    protected $httpImportHandler;

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
     * @var SplitterCsvFile
     */
    protected $splitterCsvFileHelper;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var DependentJobService
     */
    protected $dependentJob;

    /**
     * @param HttpImportHandler $httpImportHandler
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     * @param SplitterCsvFile $splitterCsvFileHelper
     * @param RegistryInterface $doctrine
     * @param DependentJobService $dependentJob
     */
    public function __construct(
        HttpImportHandler $httpImportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        SplitterCsvFile $splitterCsvFileHelper,
        RegistryInterface $doctrine,
        DependentJobService $dependentJob
    ) {
        $this->httpImportHandler = $httpImportHandler;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->splitterCsvFileHelper = $splitterCsvFileHelper;
        $this->doctrine = $doctrine;
        $this->dependentJob = $dependentJob;
    }


    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        $body = array_replace_recursive([
                'filePath' => null,
                'originFileName' => null,
                'userId' => null,
                'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
                'processorAlias' => null,
                'options' => [],
            ], $body);

        if (! $body['filePath'] || ! $body['processorAlias'] || ! $body['userId']) {
            $this
                ->logger
                ->critical(
                    sprintf('Got invalid message. body: %s', $message->getBody()),
                    ['message' => $message]
                );

            return self::REJECT;
        }

        $user = $this->doctrine->getRepository(User::class)->find($body['userId']);
        if (! $user instanceof User) {
            $this->logger->error(
                sprintf('User not found. id: %s', $body['userId']),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $parentMessageId = $message->getMessageId();
        try {
            $files = $this->splitterCsvFileHelper->getSplitFiles($body['filePath']);
        } catch (InvalidItemException $e) {
            $this->logger->warning(
                sprintf('Import of file %s failed', $body['originFileName']),
                ['message' => $message]
            );

            $this->producer->send(
                Topics::SEND_IMPORT_ERROR_INFO,
                [
                    'filePath' => $body['filePath'],
                    'originFileName' => $body['originFileName'],
                    'userId' => $body['userId'],
                    'subscribedTopic' => static::getSubscribedTopics(),
                    'errorMessage' => 'The import file could not be imported due to a fatal error. ' .
                                      'Please check its integrity and try again!',
                ]
            );

            return self::REJECT;
        }

        $result = $this->jobRunner->runUnique(
            $parentMessageId,
            sprintf('%s:%s:%s', static::getMessageName(), $body['processorAlias'], $parentMessageId),
            function (JobRunner $jobRunner, Job $job) use ($body, $files, $parentMessageId) {
                foreach ($files as $key => $file) {
                    $jobRunner->createDelayed(
                        sprintf(
                            '%s:%s:%s:chunk.%s',
                            static::getMessageName(),
                            $body['processorAlias'],
                            $parentMessageId,
                            ++$key
                        ),
                        function (JobRunner $jobRunner, Job $child) use ($body, $file, $key) {
                            $body['filePath'] = $file;
                            $this->producer->send(
                                static::getTopicForChildJob(),
                                array_merge($body, ['jobId' => $child->getId()])
                            );
                        }
                    );
                }
                $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
                $context->addDependentJob(
                    Topics::SEND_IMPORT_NOTIFICATION,
                    [
                        'rootImportJobId' => $job->getRootJob()->getId(),
                        'filePath' => $body['filePath'],
                        'originFileName' => $body['originFileName'],
                        'userId' => $body['userId'],
                        'subscribedTopic' => static::getSubscribedTopics(),
                    ]
                );
                $this->dependentJob->saveDependentJob($context);

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}
