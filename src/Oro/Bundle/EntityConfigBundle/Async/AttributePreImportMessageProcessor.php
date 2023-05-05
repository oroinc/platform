<?php

namespace Oro\Bundle\EntityConfigBundle\Async;

use Oro\Bundle\EntityConfigBundle\Async\Topic\AttributeImportTopic;
use Oro\Bundle\EntityConfigBundle\Async\Topic\AttributePreImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Import\PreImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topic\SaveImportExportResultTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\SendImportNotificationTopic;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * Responsible for splitting import process into a set of independent jobs each processing its own
 * chunk of data to be run one by one. Notifies user by email if import error occurs.
 */
class AttributePreImportMessageProcessor extends PreImportMessageProcessor
{
    public static function getSubscribedTopics()
    {
        return [AttributePreImportTopic::getName()];
    }

    protected function processJob(MessageInterface $message, array $body, array $files)
    {
        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner, Job $job) use ($body, $files) {
                $body['options']['importVersion'] = time();
                $this->dispatchBeforeChunksEvent($body);
                $this->createFinishJobs($job, $body);

                $subJobs = [];
                $jobName = $job->getName();
                foreach ($files as $key => $file) {
                    $jobRunner->createDelayed(
                        sprintf('%s:chunk.%s', $jobName, ++$key),
                        function (JobRunner $jobRunner, Job $child) use ($body, $file, $key, &$subJobs) {
                            $body['fileName'] = $file;
                            $body['jobId'] = $child->getId();

                            $subJobs[] = $body;
                        }
                    );
                }

                $subJob = array_shift($subJobs) ?: [];
                $subJob['subJobs'] = $subJobs;
                $this->producer->send(AttributeImportTopic::getName(), $subJob);

                return true;
            }
        );
        $this->fileManager->deleteFile($body['fileName']);

        return $result;
    }

    private function createFinishJobs(Job $job, array $body): void
    {
        $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
        $context->addDependentJob(
            SendImportNotificationTopic::getName(),
            [
                'rootImportJobId' => $job->getRootJob()->getId(),
                'originFileName' => $body['originFileName'],
                'userId' => $body['userId'],
                'process' => $body['process'],
            ]
        );
        $context->addDependentJob(SaveImportExportResultTopic::getName(), [
            'jobId' => $job->getRootJob()->getId(),
            'userId' => $body['userId'],
            'type' => $body['process'],
            'entity' => $this->importHandler->getEntityName(
                $body['process'],
                $body['processorAlias']
            ),
            'options' => $body['options']
        ]);
        $this->dependentJob->saveDependentJob($context);
    }
}
