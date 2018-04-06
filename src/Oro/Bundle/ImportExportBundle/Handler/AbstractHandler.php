<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Job\Job;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\File\BatchFileManager;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Reader\ReaderChain;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Abstract class for export/import handlers.
 */
abstract class AbstractHandler
{
    /**
     * @var JobExecutor
     */
    protected $jobExecutor;

    /**
     * @var ProcessorRegistry
     */
    protected $processorRegistry;

    /**
     * @var ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var WriterChain
     */
    protected $writerChain;

    /**
     * @var ReaderChain
     */
    protected $readerChain;

    /**
     * @var BatchFileManager
     */
    protected $batchFileManager;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @param JobExecutor         $jobExecutor
     * @param ProcessorRegistry   $processorRegistry
     * @param ConfigProvider      $entityConfigProvider
     * @param TranslatorInterface $translator
     * @param WriterChain         $writerChain
     * @param ReaderChain         $readerChain
     * @param BatchFileManager    $batchFileManager
     * @param FileManager         $fileManager
     */
    public function __construct(
        JobExecutor $jobExecutor,
        ProcessorRegistry $processorRegistry,
        ConfigProvider $entityConfigProvider,
        TranslatorInterface $translator,
        WriterChain $writerChain,
        ReaderChain $readerChain,
        BatchFileManager $batchFileManager,
        FileManager $fileManager
    ) {
        $this->jobExecutor        = $jobExecutor;
        $this->processorRegistry  = $processorRegistry;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator = $translator;
        $this->writerChain = $writerChain;
        $this->readerChain = $readerChain;
        $this->batchFileManager = $batchFileManager;
        $this->fileManager = $fileManager;
    }

    /**
     * @param string $entityClass
     * @return string
     */
    protected function getEntityPluralName($entityClass)
    {
        if ($this->entityConfigProvider->hasConfig($entityClass)) {
            $label = $this->entityConfigProvider->getConfig($entityClass)->get('plural_label', false, 'entitites');
            $label = mb_strtolower($this->translator->trans($label));
        } else {
            $label = $this->translator->trans('oro.importexport.message.entities.label');
        }

        return $label;
    }

    /**
     * Method for getting reader from Akeneo Job configuration steps
     *
     * @param string $jobName
     * @param string $processorType
     * @return ItemReaderInterface|null
     */
    protected function getJobReader($jobName, $processorType)
    {
        /**  @var Job $job */
        $job = $this->jobExecutor->getJob(
            $processorType,
            $jobName
        );

        if (! $job) {
            throw new LogicException(sprintf('Job "%s" must be configured', $jobName));
        }

        /** @var ItemStep $step  */
        $step = $job->getStep($processorType);

        if (! $step) {
            throw new LogicException(sprintf('Step of Job\'s "%s" must be configured', $jobName));
        }

        return $step->getReader();
    }

    /**
     * @param string $jobName
     * @param string $processorType
     * @return ItemWriterInterface|null
     */
    protected function getJobWriter($jobName, $processorType)
    {
        /**  @var Job $job */
        $job = $this->jobExecutor->getJob(
            $processorType,
            $jobName
        );

        if (! $job) {
            throw new LogicException(sprintf('Job "%s" must be configured', $jobName));
        }

        /** @var ItemStep $step */
        $step = $job->getStep($processorType);

        if (! $step) {
            throw new LogicException(sprintf('Step of Job\'s "%s" must be configured', $jobName));
        }

        return $step->getWriter();
    }
}
