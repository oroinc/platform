<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Handler;

use Oro\Bundle\ImportExportBundle\Handler\AbstractImportHandler;
use Oro\Bundle\ImportExportBundle\Handler\CliImportHandler;

class CliImportHandlerTest extends ImportHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getImportHandler(): AbstractImportHandler
    {
        return new CliImportHandler(
            $this->jobExecutor,
            $this->processorRegistry,
            $this->configProvider,
            $this->translator,
            $this->writerChain,
            $this->readerChain,
            $this->batchFileManager,
            $this->fileManager
        );
    }
}
