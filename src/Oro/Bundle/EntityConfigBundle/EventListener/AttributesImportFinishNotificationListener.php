<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\EntityConfigBundle\ImportExport\Configuration\AttributeImportExportConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\WebSocket\AttributesImportTopicSender;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Job\JobResult;

/**
 * This listener notifies about import finish via web sockets.
 */
class AttributesImportFinishNotificationListener
{
    const ENTITY_CONFIG_MODEL_ID_KEY = 'entity_id';

    /**
     * @var AttributesImportTopicSender
     */
    private $topicSender;

    /**
     * @param AttributesImportTopicSender $topicSender
     */
    public function __construct(AttributesImportTopicSender $topicSender)
    {
        $this->topicSender = $topicSender;
    }

    /**
     * @param AfterJobExecutionEvent $event
     */
    public function onAfterAttributesImport(AfterJobExecutionEvent $event)
    {
        $jobExecution = $event->getJobExecution();
        $jobResult = $event->getJobResult();

        if (!$this->isApplicable($jobResult, $jobExecution)) {
            return;
        }

        $this->topicSender->send((int)$jobExecution->getExecutionContext()->get(self::ENTITY_CONFIG_MODEL_ID_KEY));
    }

    /**
     * @param JobResult $jobResult
     * @param JobExecution $jobExecution
     * @return bool
     */
    protected function isApplicable(JobResult $jobResult, JobExecution $jobExecution): bool
    {
        if (!$jobResult->isSuccessful()) {
            return false;
        }

        if (AttributeImportExportConfigurationProvider::ATTRIBUTE_IMPORT_FROM_CSV_JOB_NAME
            !== $jobExecution->getJobInstance()->getAlias()) {
            return false;
        }

        return true;
    }
}
