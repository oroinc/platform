<?php
namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Topic\ExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * Class create message and generate list of records for export which are later used in child job.
 * Responsible for running the main export job.
 */
class PreExportMessageProcessor extends PreExportMessageProcessorAbstract
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [PreExportTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExportingEntityIds(array $body)
    {
        if (isset($body['organizationId'])) {
            $body['options']['organization'] = $this->doctrineHelper
                ->getEntityRepository(Organization::class)
                ->find($body['organizationId']);
        }

        $ids = $this->exportHandler->getExportingEntityIds(
            $body['jobName'],
            $body['exportType'],
            $body['processorAlias'],
            $body['options']
        );

        return $ids;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDelayedJobCallback(array $body, array $ids = [])
    {
        if (!empty($ids)) {
            $body['options']['ids'] = $ids;
        }

        return function (JobRunner $jobRunner, Job $child) use ($body) {
            $this->producer->send(
                ExportTopic::getName(),
                new Message(
                    array_merge($body, ['jobId' => $child->getId()]),
                    MessagePriority::LOW
                )
            );
        };
    }

    /**
     * {@inheritDoc}
     */
    protected function getMessageBody(MessageInterface $message)
    {
        $messageBody = $message->getBody();
        $messageBody['entity'] = $this->exportHandler->getEntityName(
            $messageBody['exportType'],
            $messageBody['processorAlias']
        );

        return $messageBody;
    }
}
