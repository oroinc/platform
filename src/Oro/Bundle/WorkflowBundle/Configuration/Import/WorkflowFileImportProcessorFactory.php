<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;

class WorkflowFileImportProcessorFactory implements ImportProcessorFactoryInterface
{
    /** @var ConfigFileReaderInterface */
    private $reader;

    /**
     * @param ConfigFileReaderInterface $reader
     */
    public function __construct(ConfigFileReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    /** {@inheritdoc} */
    public function isApplicable($import): bool
    {
        $import = (array)$import;

        if (count($import) !== 4) {
            return false;
        }

        return isset($import['workflow'], $import['as'], $import['resource'], $import['replace']);
    }

    /** {@inheritdoc} */
    public function create($import): ConfigImportProcessorInterface
    {
        if (!$this->isApplicable($import)) {
            throw new \InvalidArgumentException('Not applicable import options got. an not create processor.');
        }

        $importProcessor = new WorkflowFileImportProcessor($this->reader, $import['resource']);
        $importProcessor->setTarget($import['as']);
        $importProcessor->setResource($import['workflow']);
        $importProcessor->setReplacements((array)$import['replace']);

        return $importProcessor;
    }
}
