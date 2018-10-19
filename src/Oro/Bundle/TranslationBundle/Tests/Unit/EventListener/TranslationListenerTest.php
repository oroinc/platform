<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\TranslationBundle\EventListener\TranslationListener;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class TranslationListenerTest extends \PHPUnit\Framework\TestCase
{
    const JOB_NAME = 'test_job_name';

    /** @var \PHPUnit\Framework\MockObject\MockObject|DynamicTranslationMetadataCache */
    protected $metadataCache;

    /** @var TranslationListener */
    protected $listener;

    protected function setUp()
    {
        $this->metadataCache = $this->getMockBuilder(DynamicTranslationMetadataCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new TranslationListener($this->metadataCache, self::JOB_NAME);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->metadataCache);
    }

    public function testOnAfterImportTranslationsJobFailed()
    {
        $event = $this->getAfterJobExecutionEvent();

        $this->metadataCache->expects($this->never())->method($this->anything());

        $this->listener->onAfterImportTranslations($event);
    }

    public function testOnAfterImportTranslationsJobSuccessfulUnknownJob()
    {
        $event = $this->getAfterJobExecutionEvent(true, 'unknown');

        $this->metadataCache->expects($this->never())->method($this->anything());

        $this->listener->onAfterImportTranslations($event);
    }

    public function testOnAfterImportTranslationsJobSuccessfulWithoutLanguageCode()
    {
        $event = $this->getAfterJobExecutionEvent(true, self::JOB_NAME);

        $this->metadataCache->expects($this->never())->method($this->anything());

        $this->listener->onAfterImportTranslations($event);
    }

    public function testOnAfterImportTranslationsJobSuccessfulWithLanguageCode()
    {
        $language = 'en';

        $event = $this->getAfterJobExecutionEvent(true, self::JOB_NAME, $language);

        $this->metadataCache->expects($this->once())->method('updateTimestamp')->with($language);

        $this->listener->onAfterImportTranslations($event);
    }

    /**
     * @param bool $jobIsSuccessful
     * @param string $jobLabel
     * @param string $languageCode
     *
     * @return AfterJobExecutionEvent
     */
    protected function getAfterJobExecutionEvent($jobIsSuccessful = false, $jobLabel = '', $languageCode = '')
    {
        $executionContext = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $executionContext->expects($this->any())->method('get')->with('language_code')->willReturn($languageCode);

        $jobExecution = $this->getMockBuilder(JobExecution::class)->disableOriginalConstructor()->getMock();
        $jobExecution->expects($this->any())->method('getLabel')->willReturn($jobLabel);
        $jobExecution->expects($this->any())->method('getExecutionContext')->willReturn($executionContext);

        $jobResult = $this->getMockBuilder(JobResult::class)->disableOriginalConstructor()->getMock();
        $jobResult->expects($this->once())->method('isSuccessful')->willReturn($jobIsSuccessful);

        return new AfterJobExecutionEvent($jobExecution, $jobResult);
    }
}
