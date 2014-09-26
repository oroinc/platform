<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\File\FileSystemOperator;

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
     * @param JobExecutor $jobExecutor
     * @param ProcessorRegistry $processorRegistry
     * @param FileSystemOperator $fileSystemOperator
     */
    public function __construct(
        JobExecutor $jobExecutor,
        ProcessorRegistry $processorRegistry,
        FileSystemOperator $fileSystemOperator
    ) {
        $this->jobExecutor        = $jobExecutor;
        $this->processorRegistry  = $processorRegistry;
        $this->fileSystemOperator = $fileSystemOperator;
    }
}
