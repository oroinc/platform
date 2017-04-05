<?php
namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\NotificationBundle\Async\Topics as NotifcationTopics;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;

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
            $this->logger->critical(
                sprintf('Got invalid message. body: %s', $body),
                ['message' => $body]
            );

            return false;
        }

        $body = array_replace_recursive([
            'notifyEmail' => null,
            'options' => []
        ], $body);

        $body['options']['batch_size'] = $this->batchSize;

        return $body;
    }

    /**
     * {@inheritdoc}
     */
    protected function processJob($parentMessageId, $body, $files)
    {
        $jobName = sprintf(
            'oro_cli:%s:%s:%s:%s',
            $body['process'],
            $body['processorAlias'],
            $body['jobName'],
            $body['notifyEmail']
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
            '[PreCliImportMessageProcessor] An error occurred while reading file %s: "%s"',
            $body['originFileName'],
            $error
        );

        $this->logger->critical($errorMessage, ['message' => $body]);

        if (isset($body['notifyEmail'])) {
            $this->producer->send(NotifcationTopics::SEND_NOTIFICATION_EMAIL, [
                'fromEmail' => $this->configManager->get('oro_notification.email_notification_sender_email'),
                'fromName' => $this->configManager->get('oro_notification.email_notification_sender_name'),
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
