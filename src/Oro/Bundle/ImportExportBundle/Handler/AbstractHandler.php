<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\File\FileSystemOperator;
use Symfony\Component\Translation\TranslatorInterface;

class AbstractHandler
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
     * @var FileSystemOperator
     */
    protected $fileSystemOperator;

    /**
     * @var ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param JobExecutor $jobExecutor
     * @param ProcessorRegistry $processorRegistry
     * @param FileSystemOperator $fileSystemOperator
     * @param ConfigProvider $entityConfigProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        JobExecutor $jobExecutor,
        ProcessorRegistry $processorRegistry,
        FileSystemOperator $fileSystemOperator,
        ConfigProvider $entityConfigProvider,
        TranslatorInterface $translator
    ) {
        $this->jobExecutor        = $jobExecutor;
        $this->processorRegistry  = $processorRegistry;
        $this->fileSystemOperator = $fileSystemOperator;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator = $translator;
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
}
