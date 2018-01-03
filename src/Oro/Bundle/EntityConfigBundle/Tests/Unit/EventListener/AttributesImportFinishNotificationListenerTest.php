<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\EntityConfigBundle\ImportExport\Configuration\AttributeImportExportConfigurationProvider;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\SyncBundle\Content\SimpleTagGenerator;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Oro\Bundle\SyncBundle\Content\TopicSender;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\EntityConfigBundle\EventListener\AttributesImportFinishNotificationListener;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributesImportFinishNotificationListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var TopicSender|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $topicSender;

    /**
     * @var AttributesImportFinishNotificationListener
     */
    protected $attributesImportFinishNotificationListener;

    protected function setUp()
    {
        $this->topicSender = $this->createMock(TopicSender::class);
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
            ->method('sendToAll');

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
            ->method('sendToAll');

        $this->attributesImportFinishNotificationListener->onAfterAttributesImport($event);
    }

    public function testOnAfterAttributesImport()
    {
        $jobResult = (new JobResult())->setSuccessful(true);
        $entityId = 777;
        $context = (new ExecutionContext())->put(
            AttributesImportFinishNotificationListener::FIELD_CONFIG_MODEL_ID_KEY,
            $entityId
        );
        /** @var \Akeneo\Bundle\BatchBundle\Entity\JobExecution $jobExecution */
        $jobExecution = $this->getEntity(JobExecution::class, [
            'jobInstance' => $this->getEntity(JobInstance::class, [
                'alias' => AttributeImportExportConfigurationProvider::ATTRIBUTE_IMPORT_FROM_CSV_JOB_NAME
            ]),
            'executionContext' => $context
        ]);
        $event = new AfterJobExecutionEvent($jobExecution, $jobResult);

        $tags = ['tag1'];
        $tagGenerator = $this->createMock(TagGeneratorInterface::class);
        $tagGenerator
            ->expects($this->once())
            ->method('generate')
            ->with([
                SimpleTagGenerator::STATIC_NAME_KEY =>
                    AttributesImportFinishNotificationListener::ATTRIBUTE_IMPORT_FINISH_TAG,
                SimpleTagGenerator::IDENTIFIER_KEY => [$entityId]
            ])
            ->willReturn($tags);

        $this->topicSender
            ->expects($this->once())
            ->method('getGenerator')
            ->willReturn($tagGenerator);

        $this->topicSender
            ->expects($this->once())
            ->method('sendToAll')
            ->with($tags);

        $this->attributesImportFinishNotificationListener->onAfterAttributesImport($event);

    }
}
