<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigFinderFactory;
use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;

/**
 * Creates workflow import processor supervisors for handling workflow import operations.
 *
 * Factory implementation that creates and configures {@see WorkflowImportProcessorSupervisor} instances
 * for processing workflow imports with the format: `{workflow: <resource>, as: <target>, replace: <replacements>}`.
 * Validates import format applicability and wraps {@see WorkflowImportProcessor} instances with supervision
 * to manage import processing state and prevent circular references. This factory is responsible for
 * instantiating the appropriate import processor infrastructure for workflow configuration imports.
 */
class WorkflowImportProcessorSupervisorFactory implements ImportProcessorFactoryInterface
{
    /** @var ConfigFileReaderInterface */
    private $reader;

    /** @var ConfigFinderFactory */
    private $configFinderBuilder;

    /** @var WorkflowImportProcessorSupervisor */
    private $importSupervisor;

    public function __construct(ConfigFileReaderInterface $reader, WorkflowConfigFinderBuilder $configFinderBuilder)
    {
        $this->reader = $reader;
        $this->configFinderBuilder = $configFinderBuilder;
    }

    #[\Override]
    public function isApplicable($import): bool
    {
        $import = (array)$import;

        return count($import) === 3 && isset($import['workflow'], $import['as'], $import['replace']);
    }

    #[\Override]
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

    private function supervise(WorkflowImportProcessor $workflowImport): WorkflowImportProcessorSupervisor
    {
        if (!$this->importSupervisor) {
            $this->importSupervisor = new WorkflowImportProcessorSupervisor();
        }

        $this->importSupervisor->addImportProcessor($workflowImport);

        return $this->importSupervisor;
    }
}
