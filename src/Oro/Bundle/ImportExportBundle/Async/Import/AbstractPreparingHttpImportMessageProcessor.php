<?php
namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\File\SplitterCsvFile;

use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Bundle\ImportExportBundle\Async\Topics;

abstract class AbstractPreparingHttpImportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
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
                'fileName' => null,
                'userId' => null,
                'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
                'processorAlias' => null,
                'options' => [],
            ], $body);

        if (! $body['fileName'] || ! $body['processorAlias'] || ! $body['userId']) {
            $this->logger->critical(
                sprintf('Invalid message: %s', $body),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $user = $this->doctrine->getRepository(User::class)->find($body['userId']);
        if (! $user instanceof User) {
            $this->logger->error(
                sprintf('User not found: %s', $body['userId']),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $files = $this->splitterCsvFileHelper->getSplitFiles($body['fileName']);
        $parentMessageId = $message->getMessageId();
        $result = $this->jobRunner->runUnique(
            $parentMessageId,
            sprintf('%s:%s:%s', static::getMessageName(), $body['processorAlias'], $parentMessageId),
            function (JobRunner $jobRunner, Job $job) use ($body, $files, $parentMessageId) {
                foreach ($files as $key=>$file) {
                    $jobRunner->createDelayed(
                        sprintf('%s:%s%s:chunk.%s', static::getMessageName(), $body['processorAlias'], $parentMessageId, ++$key),
                        function (JobRunner $jobRunner, Job $child) use ($body, $file) {
                            $body['fileName'] = $file;
                            $this->producer->send(static::getTopicsForChildJob(), array_merge($body, ['jobId' => $child->getId()]));
                        }
                    );
                }
                $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
                $context->addDependentJob(
                    Topics::SEND_IMPORT_NOTIFICATION,
                    [
                        'rootImportJobId' => $job->getRootJob()->getId(),
                        'fileName' => $body['fileName'] ,
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

    /**
     * {@inheritdoc}
     */
    abstract public static function getSubscribedTopics();

    abstract public static function getTopicsForChildJob();

    /**
     * return message name that will be show in Message
     * @return string
     */
    abstract public static function getMessageName();
}
