<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Handler;

use Oro\Bundle\ImportExportBundle\Handler\AbstractImportHandler;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;

class HttpImportHandlerTest extends ImportHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getImportHandler(): AbstractImportHandler
    {
        return new HttpImportHandler(
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
