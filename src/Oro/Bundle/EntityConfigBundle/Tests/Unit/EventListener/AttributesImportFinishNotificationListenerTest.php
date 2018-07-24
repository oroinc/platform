<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\EntityConfigBundle\EventListener\AttributesImportFinishNotificationListener;
use Oro\Bundle\EntityConfigBundle\ImportExport\Configuration\AttributeImportExportConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\WebSocket\AttributesImportTopicSender;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributesImportFinishNotificationListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const ENTITY_ID = 27;

    /**
     * @var AttributesImportTopicSender|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $topicSender;

    /**
     * @var AttributesImportFinishNotificationListener
     */
    protected $attributesImportFinishNotificationListener;

    protected function setUp()
    {
        $this->topicSender = $this->createMock(AttributesImportTopicSender::class);
        $this->attributesImportFinishNotificationListener =
            new AttributesImportFinishNotificationListener($this->topicSender);
    }

    public function testOnAfterAttributesImportWhenNotIsSuccessful()
    {
        $jobResult = (new JobResult())->setSuccessful(false);
        /** @var \Akeneo\Bundle\BatchBundle\Entity\JobExecution $jobExecution */
        $jobExecution = $this->getEntity(JobExecution::class);
        $event = new AfterJobExecutionEvent($jobExecution, $jobResult);

        $this->topicSender
            ->expects($this->never())
            ->method('send');

        $this->attributesImportFinishNotificationListener->onAfterAttributesImport($event);
    }

    public function testOnAfterAttributesImportWhenJobInstanceAliasIsWrong()
    {
        $jobResult = (new JobResult())->setSuccessful(true);
        /** @var \Akeneo\Bundle\BatchBundle\Entity\JobExecution $jobExecution */
        $jobExecution = $this->getEntity(JobExecution::class, [
            'jobInstance' => $this->getEntity(JobInstance::class, ['alias' => 'some_alias'])
        ]);
        $event = new AfterJobExecutionEvent($jobExecution, $jobResult);

        $this->topicSender
            ->expects($this->never())
            ->method('send');

        $this->attributesImportFinishNotificationListener->onAfterAttributesImport($event);
    }

    public function testOnAfterAttributesImport()
    {
        $jobResult = (new JobResult())->setSuccessful(true);
        $context = (new ExecutionContext())->put(
            AttributesImportFinishNotificationListener::ENTITY_CONFIG_MODEL_ID_KEY,
            self::ENTITY_ID
        );
        /** @var \Akeneo\Bundle\BatchBundle\Entity\JobExecution $jobExecution */
        $jobExecution = $this->getEntity(JobExecution::class, [
            'jobInstance' => $this->getEntity(JobInstance::class, [
                'alias' => AttributeImportExportConfigurationProvider::ATTRIBUTE_IMPORT_FROM_CSV_JOB_NAME
            ]),
            'executionContext' => $context
        ]);
        $event = new AfterJobExecutionEvent($jobExecution, $jobResult);

        $this->topicSender
            ->expects($this->once())
            ->method('send')
            ->with(self::ENTITY_ID);

        $this->attributesImportFinishNotificationListener->onAfterAttributesImport($event);
    }
}
