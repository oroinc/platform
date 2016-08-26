<?php

namespace Oro\Bundle\WorkflowBundle\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class ExecuteProcessJobProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;
    
    /**
     * @var ProcessHandler
     */
    private $processHandler;

    public function __construct(DoctrineHelper $doctrineHelper, ProcessHandler $processHandler)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->processHandler = $processHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = array_replace_recursive(['process_job_id' => null, ], JSON::decode($message->getBody()));
        if (false == $body['process_job_id']) {
            return self::REJECT;
        }

        $processJob = $this->doctrineHelper->getEntityRepository(ProcessJob::class)->find($body['process_job_id']);
        if (!$processJob) {
            return self::REJECT;
        }

        $entityManager = $this->doctrineHelper->getEntityManager(ProcessJob::class);
        $entityManager->beginTransaction();

        try {
            $this->processHandler->handleJob($processJob);
            $entityManager->remove($processJob);
            $entityManager->flush();

            $this->processHandler->finishJob($processJob);
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();

            throw  $e;
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXECUTE_PROCESS_JOB];
    }
}
