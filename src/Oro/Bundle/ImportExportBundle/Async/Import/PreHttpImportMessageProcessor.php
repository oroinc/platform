<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\NotificationBundle\Async\Topics as NotifcationTopics;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;

/**
 * Responsible for splitting import process triggered from UI into a set of independent jobs each processing its own
 * chunk of data to be run in parallel. Notifies user by email if import error occurs.
 */
class PreHttpImportMessageProcessor extends PreImportMessageProcessorAbstract
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function setManagerRegistry(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateMessageBody($body)
    {
        if (! isset(
            $body['userId'],
            $body['jobName'],
            $body['process'],
            $body['fileName'],
            $body['originFileName']
        )) {
            $this->logger->critical('Got invalid message');

            return false;
        }

        $body = array_replace_recursive([
            'processorAlias' => null,
            'options' => [],
        ], $body);

        $body['options'][Context::OPTION_BATCH_SIZE] = $this->batchSize;

        return $body;
    }

    /**
     * {@inheritdoc}
     */
    protected function processJob($parentMessageId, $body, $files)
    {
        $uniqueJobSlug = $body['userId'];
        if (isset($body['options']['unique_job_slug'])) {
            $uniqueJobSlug = $body['options']['unique_job_slug'];
        }
        $jobName = sprintf(
            'oro:%s:%s:%s:%s',
            $body['process'],
            $body['processorAlias'],
            $body['jobName'],
            $uniqueJobSlug
        );

        $result = $this->jobRunner->runUnique(
            $parentMessageId,
            $jobName,
            function (JobRunner $jobRunner, Job $job) use ($jobName, $body, $files) {
                foreach ($files as $key => $file) {
                    $jobRunner->createDelayed(
                        sprintf('%s:chunk.%s', $jobName, ++$key),
                        function (JobRunner $jobRunner, Job $child) use ($body, $file, $key) {
                            $body['fileName'] = $file;
                            $body['options']['batch_number'] = $key;
                            $this->producer->send(
                                Topics::HTTP_IMPORT,
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
                        'originFileName' => $body['originFileName'],
                        'userId' => $body['userId'],
                        'process' => $body['process'],
                    ]
                );
                $this->dependentJob->saveDependentJob($context);

                return true;
            }
        );
        $this->fileManager->deleteFile($body['fileName']);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::PRE_HTTP_IMPORT];
    }

    /**
     * {@inheritdoc}
     */
    protected function sendErrorNotification(array $body, $error)
    {
        $errorMessage = sprintf(
            'An error occurred while reading file %s: "%s"',
            $body['originFileName'],
            $error
        );

        $this->logger->critical($errorMessage);

        $user = $this->managerRegistry
            ->getManagerForClass(User::class)
            ->getRepository(User::class)
            ->find($body['userId']);

        if (! $user instanceof User) {
            $this->logger->critical(
                sprintf('User not found. Id: %s', $body['userId'])
            );

            return;
        }

        $sender = $this->notificationSettings->getSender();
        $this->producer->send(NotifcationTopics::SEND_NOTIFICATION_EMAIL, [
            'sender' => $sender->toArray(),
            'toEmail' => $user->getEmail(),
            'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_ERROR,
            'recipientUserId' => $user->getId(),
            'body' => [
                'originFileName' => $body['originFileName'],
                'error' =>  'The import file could not be imported due to a fatal error. ' .
                    'Please check its integrity and try again!',
            ],
            'contentType' => 'text/html',
        ]);
    }
}
