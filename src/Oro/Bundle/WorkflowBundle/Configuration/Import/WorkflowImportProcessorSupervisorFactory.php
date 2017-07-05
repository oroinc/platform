<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigFinderFactory;
use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;

class WorkflowImportProcessorSupervisorFactory implements ImportProcessorFactoryInterface
{
    /** @var ConfigFileReaderInterface */
    private $reader;

    /** @var ConfigFinderFactory */
    private $configFinderBuilder;

    /** @var WorkflowImportProcessorSupervisor */
    private $importSupervisor;

    /**
     * @param ConfigFileReaderInterface $reader
     * @param WorkflowConfigFinderBuilder $configFinderBuilder
     */
    public function __construct(ConfigFileReaderInterface $reader, WorkflowConfigFinderBuilder $configFinderBuilder)
    {
        $this->reader = $reader;
        $this->configFinderBuilder = $configFinderBuilder;
    }

    /** {@inheritdoc} */
    public function isApplicable($import): bool
    {
        $import = (array)$import;

        return count($import) === 3 && isset($import['workflow'], $import['as'], $import['replace']);
    }

    /** {@inheritdoc} */
    public function create($import): ConfigImportProcessorInterface
    {
        if (!$this->isApplicable($import)) {
            throw new \InvalidArgumentException(
                'Can not create import processor. Import format is not supported.'
            );
        }

        $importProcessor = new WorkflowImportProcessor($this->reader, $this->configFinderBuilder);
        $importProcessor->setResource($import['workflow']);
        $importProcessor->setTarget($import['as']);
        $importProcessor->setReplacements((array)$import['replace']);

        return $this->supervise($importProcessor);
    }

    /**
     * @param WorkflowImportProcessor $workflowImport
     * @return WorkflowImportProcessorSupervisor
     */
    private function supervise(WorkflowImportProcessor $workflowImport): WorkflowImportProcessorSupervisor
    {
        if (!$this->importSupervisor) {
            $this->importSupervisor = new WorkflowImportProcessorSupervisor();
        }

        $this->importSupervisor->addImportProcessor($workflowImport);

        return $this->importSupervisor;
    }
}
