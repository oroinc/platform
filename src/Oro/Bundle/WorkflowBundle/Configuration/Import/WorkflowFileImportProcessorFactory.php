<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Produces instance of import processor that corresponds to specific workflow file import part matched in config.
 */
class WorkflowFileImportProcessorFactory implements ImportProcessorFactoryInterface
{
    /** @var ConfigFileReaderInterface */
    private $reader;

    /** @var FileLocatorInterface */
    private $fileLocator;

    public function __construct(ConfigFileReaderInterface $reader, FileLocatorInterface $fileLocator)
    {
        $this->reader = $reader;
        $this->fileLocator = $fileLocator;
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

        $importProcessor = new WorkflowFileImportProcessor($this->reader, $import['resource'], $this->fileLocator);
        $importProcessor->setTarget($import['as']);
        $importProcessor->setResource($import['workflow']);
        $importProcessor->setReplacements((array)$import['replace']);

        return $importProcessor;
    }
}
