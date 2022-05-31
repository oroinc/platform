<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\TranslationBundle\EventListener\ClearDynamicTranslationCacheImportListener;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache;

class ClearDynamicTranslationCacheImportListenerTest extends \PHPUnit\Framework\TestCase
{
    private const JOB_NAME = 'test_job_name';

    /** @var DynamicTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    private $dynamicTranslationCache;

    /** @var ClearDynamicTranslationCacheImportListener */
    private $listener;

    protected function setUp(): void
    {
        $this->dynamicTranslationCache = $this->createMock(DynamicTranslationCache::class);

        $this->listener = new ClearDynamicTranslationCacheImportListener(
            $this->dynamicTranslationCache,
            self::JOB_NAME
        );
    }

    public function testOnAfterImportTranslationsJobFailed()
    {
        $event = $this->getAfterJobExecutionEvent();

        $this->dynamicTranslationCache->expects($this->never())
            ->method($this->anything());

        $this->listener->onAfterImportTranslations($event);
    }

    public function testOnAfterImportTranslationsJobSuccessfulUnknownJob()
    {
        $event = $this->getAfterJobExecutionEvent(true, 'unknown');

        $this->dynamicTranslationCache->expects($this->never())
            ->method($this->anything());

        $this->listener->onAfterImportTranslations($event);
    }

    public function testOnAfterImportTranslationsJobSuccessfulWithoutLanguageCode()
    {
        $event = $this->getAfterJobExecutionEvent(true, self::JOB_NAME);

        $this->dynamicTranslationCache->expects($this->never())
            ->method($this->anything());

        $this->listener->onAfterImportTranslations($event);
    }

    public function testOnAfterImportTranslationsJobSuccessfulWithLanguageCode()
    {
        $locale = 'en';

        $event = $this->getAfterJobExecutionEvent(true, self::JOB_NAME, $locale);

        $this->dynamicTranslationCache->expects($this->once())
            ->method('delete')
            ->with([$locale]);

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
