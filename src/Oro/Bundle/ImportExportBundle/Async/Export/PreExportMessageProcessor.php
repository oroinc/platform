<?php
namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Util\JSON;

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
        return [Topics::PRE_EXPORT];
    }

    /**
     * {@inheritDoc}
     */
    protected function getJobUniqueName(array $body)
    {
        return sprintf('oro_importexport.pre_export.%s.user_%s', $body['jobName'], $this->getUser()->getId());
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
        if (! empty($ids)) {
            $body['options']['ids'] = $ids;
        }

        return function (JobRunner $jobRunner, Job $child) use ($body) {
            $this->producer->send(
                Topics::EXPORT,
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
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'jobName' => null,
            'processorAlias' => null,
            'outputFormat' => 'csv',
            'organizationId' => null,
            'exportType' => ProcessorRegistry::TYPE_EXPORT,
            'options' => [],
        ], $body);

        $body['entity'] = $this->exportHandler->getEntityName(
            $body['exportType'],
            $body['processorAlias']
        );

        if (! isset($body['jobName'], $body['processorAlias'])) {
            $this->logger->critical('Got invalid message');

            return false;
        }

        return $body;
    }
}
