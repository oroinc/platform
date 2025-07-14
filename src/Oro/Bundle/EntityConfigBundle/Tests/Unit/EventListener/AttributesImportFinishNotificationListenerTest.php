<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\EntityConfigBundle\EventListener\AttributesImportFinishNotificationListener;
use Oro\Bundle\EntityConfigBundle\ImportExport\Configuration\AttributeImportExportConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\WebSocket\AttributesImportTopicSender;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributesImportFinishNotificationListenerTest extends TestCase
{
    use EntityTrait;

    private const ENTITY_ID = 27;

    private AttributesImportTopicSender&MockObject $topicSender;
    private AttributesImportFinishNotificationListener $attributesImportFinishNotificationListener;

    #[\Override]
    protected function setUp(): void
    {
        $this->topicSender = $this->createMock(AttributesImportTopicSender::class);

        $this->attributesImportFinishNotificationListener = new AttributesImportFinishNotificationListener(
            $this->topicSender
        );
    }

    public function testOnAfterAttributesImportWhenNotIsSuccessful(): void
    {
        $jobResult = (new JobResult())->setSuccessful(false);
        $jobExecution = $this->getEntity(JobExecution::class);
        $event = new AfterJobExecutionEvent($jobExecution, $jobResult);

        $this->topicSender->expects($this->never())
            ->method('send');

        $this->attributesImportFinishNotificationListener->onAfterAttributesImport($event);
    }

    public function testOnAfterAttributesImportWhenJobInstanceAliasIsWrong(): void
    {
        $jobResult = (new JobResult())->setSuccessful(true);
        $jobExecution = $this->getEntity(JobExecution::class, [
            'jobInstance' => $this->getEntity(JobInstance::class, ['alias' => 'some_alias'])
        ]);
        $event = new AfterJobExecutionEvent($jobExecution, $jobResult);

        $this->topicSender->expects($this->never())
            ->method('send');

        $this->attributesImportFinishNotificationListener->onAfterAttributesImport($event);
    }

    public function testOnAfterAttributesImport(): void
    {
        $jobResult = (new JobResult())->setSuccessful(true);
        $context = (new ExecutionContext())->put(
            AttributesImportFinishNotificationListener::ENTITY_CONFIG_MODEL_ID_KEY,
            self::ENTITY_ID
        );
        $jobExecution = $this->getEntity(JobExecution::class, [
            'jobInstance' => $this->getEntity(JobInstance::class, [
                'alias' => AttributeImportExportConfigurationProvider::ATTRIBUTE_IMPORT_FROM_CSV_JOB_NAME
            ]),
            'executionContext' => $context
        ]);
        $event = new AfterJobExecutionEvent($jobExecution, $jobResult);

        $this->topicSender->expects($this->once())
            ->method('send')
            ->with(self::ENTITY_ID);

        $this->attributesImportFinishNotificationListener->onAfterAttributesImport($event);
    }
}
