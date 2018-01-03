<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\EntityConfigBundle\ImportExport\Configuration\AttributeImportExportConfigurationProvider;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\SyncBundle\Content\SimpleTagGenerator;
use Oro\Bundle\SyncBundle\Content\TopicSender;

/**
 * This listener notifies about import finish via web sockets.
 */
class AttributesImportFinishNotificationListener
{
    const FIELD_CONFIG_MODEL_ID_KEY = 'entity_id';
    const ATTRIBUTE_IMPORT_FINISH_TAG = 'AttributeImportFinish';

    /**
     * @var TopicSender
     */
    private $topicSender;

    /**
     * @param TopicSender $topicSender
     */
    public function __construct(TopicSender $topicSender)
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

        if ($this->isApplicable($jobResult, $jobExecution)) {
            $this->topicSender->sendToAll(
                $this->topicSender->getGenerator()->generate([
                    SimpleTagGenerator::STATIC_NAME_KEY => self::ATTRIBUTE_IMPORT_FINISH_TAG,
                    SimpleTagGenerator::IDENTIFIER_KEY => [
                        $jobExecution->getExecutionContext()->get(self::FIELD_CONFIG_MODEL_ID_KEY)
                    ]
                ])
            );
        }
    }

    /**
     * @param JobResult $jobResult
     * @param JobExecution $jobExecution
     * @return bool
     */
    protected function isApplicable(JobResult $jobResult, JobExecution $jobExecution)
    {
        return $jobResult->isSuccessful() &&
            (AttributeImportExportConfigurationProvider::ATTRIBUTE_IMPORT_FROM_CSV_JOB_NAME
            === $jobExecution->getJobInstance()->getAlias());
    }
}
