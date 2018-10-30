<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;

/**
 * Responsible for splitting import process triggered from CLI into a set of independent jobs each processing its own
 * chunk of data to be run in parallel. Notifies user by email if import error occurs.
 */
class PreCliImportMessageProcessor extends PreImportMessageProcessorAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function validateMessageBody($body)
    {
        if (! isset(
            $body['jobName'],
            $body['process'],
            $body['processorAlias'],
            $body['fileName'],
            $body['originFileName']
        )
        ) {
            $this->logger->critical('Got invalid message');

            return false;
        }

        $body = array_replace_recursive([
            'notifyEmail' => null,
            'options' => []
        ], $body);

        $body['options'][Context::OPTION_BATCH_SIZE] = $this->batchSize;

        return $body;
    }

    /**
     * {@inheritdoc}
     */
    protected function processJob($parentMessageId, $body, $files)
    {
        $uniqueJobSlug = $body['notifyEmail'];
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
                        function (JobRunner $jobRunner, Job $child) use ($file, $body) {
                            $body['fileName'] = $file;
                            $this->producer->send(
                                Topics::CLI_IMPORT,
                                array_merge($body, ['jobId' => $child->getId()])
                            );
                        }
                    );
                }
                if ($body['notifyEmail']) {
                    $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
                    $context->addDependentJob(
                        Topics::SEND_IMPORT_NOTIFICATION,
                        [
                            'rootImportJobId' => $job->getRootJob()->getId(),
                            'originFileName' => $body['originFileName'],
                            'notifyEmail' => $body['notifyEmail'],
                            'process' => $body['process'],
                        ]
                    );
                    $this->dependentJob->saveDependentJob($context);
                }

                return true;
            }
        );

        $this->fileManager->deleteFile($body['fileName']);

        return $result;
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

        if (isset($body['notifyEmail'])) {
            $sender = $this->notificationSettings->getSender();
            $this->producer->send(NotificationTopics::SEND_NOTIFICATION_EMAIL, [
                'sender' => $sender->toArray(),
                'toEmail' => $body['notifyEmail'],
                'template' => 'import_error',
                'body' => [
                    'originFileName' => $body['originFileName'],
                    'error' => $error,
                ],
                'contentType' => 'text/html',
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::PRE_CLI_IMPORT];
    }
}
