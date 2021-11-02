<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\TranslationBundle\EventListener\TranslationListener;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class TranslationListenerTest extends \PHPUnit\Framework\TestCase
{
    private const JOB_NAME = 'test_job_name';

    /** @var DynamicTranslationMetadataCache|\PHPUnit\Framework\MockObject\MockObject */
    private $metadataCache;

    /** @var TranslationListener */
    private $listener;

    protected function setUp(): void
    {
        $this->metadataCache = $this->createMock(DynamicTranslationMetadataCache::class);

        $this->listener = new TranslationListener($this->metadataCache, self::JOB_NAME);
    }

    public function testOnAfterImportTranslationsJobFailed()
    {
        $event = $this->getAfterJobExecutionEvent();

        $this->metadataCache->expects($this->never())
            ->method($this->anything());

        $this->listener->onAfterImportTranslations($event);
    }

    public function testOnAfterImportTranslationsJobSuccessfulUnknownJob()
    {
        $event = $this->getAfterJobExecutionEvent(true, 'unknown');

        $this->metadataCache->expects($this->never())
            ->method($this->anything());

        $this->listener->onAfterImportTranslations($event);
    }

    public function testOnAfterImportTranslationsJobSuccessfulWithoutLanguageCode()
    {
        $event = $this->getAfterJobExecutionEvent(true, self::JOB_NAME);

        $this->metadataCache->expects($this->never())
            ->method($this->anything());

        $this->listener->onAfterImportTranslations($event);
    }

    public function testOnAfterImportTranslationsJobSuccessfulWithLanguageCode()
    {
        $language = 'en';

        $event = $this->getAfterJobExecutionEvent(true, self::JOB_NAME, $language);

        $this->metadataCache->expects($this->once())
            ->method('updateTimestamp')
            ->with($language);

        $this->listener->onAfterImportTranslations($event);
    }

    private function getAfterJobExecutionEvent(
        bool $jobIsSuccessful = false,
        string $jobLabel = '',
        string $languageCode = ''
    ): AfterJobExecutionEvent {
        $executionContext = $this->createMock(ExecutionContext::class);
        $executionContext->expects($this->any())
            ->method('get')
            ->with('language_code')
            ->willReturn($languageCode);

        $jobExecution = $this->createMock(JobExecution::class);
        $jobExecution->expects($this->any())
            ->method('getLabel')
            ->willReturn($jobLabel);
        $jobExecution->expects($this->any())
            ->method('getExecutionContext')
            ->willReturn($executionContext);

        $jobResult = $this->createMock(JobResult::class);
        $jobResult->expects($this->once())
            ->method('isSuccessful')
            ->willReturn($jobIsSuccessful);

        return new AfterJobExecutionEvent($jobExecution, $jobResult);
    }
}
