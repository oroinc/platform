<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowConfigurationImportException;

/**
 * Processor responsible for importing a workflow with replacements.
 * Merges the config of the workflow currently being imported onto the existing workflow config.
 */
class WorkflowImportProcessor implements ConfigImportProcessorInterface
{
    use WorkflowImportTrait;

    /** @var ConfigFileReaderInterface */
    protected $reader;

    /** @var WorkflowConfigFinderBuilder */
    protected $configFinderBuilder;

    /** @var ConfigImportProcessorInterface */
    protected $parent;

    /** @var \SplFileInfo */
    protected $inProgress;

    public function __construct(ConfigFileReaderInterface $reader, WorkflowConfigFinderBuilder $configFinderBuilder)
    {
        $this->reader = $reader;
        $this->configFinderBuilder = $configFinderBuilder;
    }

    /** {@inheritdoc} */
    public function setParent(ConfigImportProcessorInterface $parentProcessor)
    {
        $this->parent = $parentProcessor;
    }

    public function inProgress(): bool
    {
        return null !== $this->inProgress;
    }

    public function getProgressFile(): \SplFileInfo
    {
        return $this->inProgress;
    }

    /** {@inheritdoc} */
    public function process(array $content, \SplFileInfo $contentSource): array
    {
        $this->inProgress = $contentSource;

        try {
            $content = $this->processParent($content, $contentSource);
            $resourceData = $this->findResourceData();
        } catch (WorkflowConfigurationImportException $exception) {
            if ($exception->getPrevious()) {
                //deep import exception should have `previous` already defined throwing as is
                throw $exception;
            }
            throw new WorkflowConfigurationImportException(
                sprintf(
                    'Error occurs while importing workflow for `%s`. Error: "%s" in `%s`',
                    $this->getTarget(),
                    $exception->getMessage(),
                    $contentSource->getRealPath()
                ),
                $exception
            );
        }

        $resourceData = $this->applyReplacements($resourceData);

        $content = $this->mergeImports($content, $resourceData);

        $this->inProgress = null;

        return $content;
    }

    private function findResourceData(): array
    {
        foreach ($this->configFinderBuilder->create() as $fileInfo) {
            $content = $this->processParent($this->reader->read($fileInfo), $fileInfo);
            if ($this->isResourcePresent($content)) {
                return $this->getResourceData($content);
            }
        }

        throw new WorkflowConfigurationImportException(
            sprintf('Can not find workflow `%s` for import.', $this->resource)
        );
    }

    private function processParent(array $content, \SplFileInfo $contentSource): array
    {
        if ($this->parent) {
            return $this->parent->process($content, $contentSource);
        }

        return $content;
    }
}
