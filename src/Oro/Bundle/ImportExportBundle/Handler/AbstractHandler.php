<?php

namespace Oro\Bundle\ImportExportBundle\Handler;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
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
     * @var Router
     */
    protected $router;

    /**
     * Constructor
     *
     * @param JobExecutor        $jobExecutor
     * @param ProcessorRegistry  $processorRegistry
     * @param FileSystemOperator $fileSystemOperator
     * @param Router             $router
     */
    public function __construct(
        JobExecutor $jobExecutor,
        ProcessorRegistry $processorRegistry,
        FileSystemOperator $fileSystemOperator,
        Router $router
    ) {
        $this->jobExecutor        = $jobExecutor;
        $this->processorRegistry  = $processorRegistry;
        $this->fileSystemOperator = $fileSystemOperator;
        $this->router             = $router;
    }
}
