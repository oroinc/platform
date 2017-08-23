<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowConfigurationImportException;

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
    public function setParent(ConfigImportProcessorInterface $parentProcessor)
    {
        $this->parent = $parentProcessor;
    }

    /**
     * @return bool
     */
    public function inProgress(): bool
    {
        return null !== $this->inProgress;
    }

    /**
     * @return \SplFileInfo
     */
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
            if ($this->isResourcePresent($content)) {
                $resourceData = $this->getResourceData($content);
            } else {
                $resourceData = $this->findResourceData($contentSource);
            }
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

        $content = $this->mergeConfigs($resourceData, $content);

        $this->inProgress = null;

        return $content;
    }

    /**
     * @param \SplFileInfo $contentSource
     * @return array
     */
    private function findResourceData(\SplFileInfo $contentSource): array
    {
        $finder = $this->configFinderBuilder->create();
        $finder->filter(
            function (\SplFileInfo $searchFile) use ($contentSource) {
                //skip current file
                return $contentSource->getRealPath() !== $searchFile->getRealPath();
            }
        );

        foreach ($finder as $fileInfo) {
            $content = $this->processParent($this->reader->read($fileInfo), $fileInfo);
            if ($this->isResourcePresent($content)) {
                return $this->getResourceData($content);
            }
        }

        throw new WorkflowConfigurationImportException(
            sprintf('Can not find workflow `%s` for import.', $this->resource)
        );
    }

    /**
     * @param array $content
     * @param \SplFileInfo $contentSource
     * @return array
     */
    private function processParent(array $content, \SplFileInfo $contentSource): array
    {
        if ($this->parent) {
            return $this->parent->process($content, $contentSource);
        }

        return $content;
    }
}
