<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

class DatagridExportReader extends AbstractReader
{
    /**
     * @var DatagridExportConnector
     */
    protected $gridDataReader;

    /**
     * @param ContextRegistry         $contextRegistry
     * @param DatagridExportConnector $gridDataReader
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        DatagridExportConnector $gridDataReader
    ) {
        parent::__construct($contextRegistry);
        $this->gridDataReader = $gridDataReader;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->gridDataReader->setImportExportContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        return $this->gridDataReader->read();
    }
}
