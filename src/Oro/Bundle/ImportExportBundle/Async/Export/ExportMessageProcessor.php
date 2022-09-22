<?php
namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Topic\ExportTopic;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * Responsible for getting export result.
 */
class ExportMessageProcessor extends ExportMessageProcessorAbstract
{
    /**
     * @var ExportHandler
     */
    protected $exportHandler;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function setExportHandler(ExportHandler $exportHandler): void
    {
        $this->exportHandler = $exportHandler;
    }

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper): void
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [ExportTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    protected function handleExport(array $body)
    {
        if (isset($body['organizationId'])) {
            $body['options']['organization'] = $this->doctrineHelper
                ->getEntityRepository(Organization::class)
                ->find($body['organizationId']);
        }

        return $this->exportHandler->getExportResult(
            $body['jobName'],
            $body['processorAlias'],
            $body['exportType'],
            $body['outputFormat'],
            $body['outputFilePrefix'],
            $body['options']
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getMessageBody(MessageInterface $message)
    {
        return $message->getBody();
    }
}
