<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\Import\ImportProcessorFactoryInterface;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowConfigurationImportException;

class WorkflowConfigurationImportsProcessor implements ConfigImportProcessorInterface
{
    /** @var ImportProcessorFactoryInterface[] */
    protected $importProcessorFactories = [];

    /** @var array */
    protected $processedContent = [];

    /** {@inheritdoc} */
    public function process(array $content, \SplFileInfo $contentSource): array
    {
        if (!isset($content['imports']) || !is_array($content['imports'])) {
            return $content;
        }

        $filePath = $contentSource->getRealPath();

        if (isset($this->processedContent[$filePath])) {
            return $this->processedContent[$filePath];
        }

        foreach ($this->getImportProcessors($content) as $processor) {
            $processor->setParent($this);
            $content = $processor->process($content, $contentSource);
        }

        unset($content['imports']);

        return $this->processedContent[$filePath] = $content;
    }

    /**
     * @param array $content pass by reference to clear imports that got its processors
     * @return array|ConfigImportProcessorInterface[]
     */
    private function getImportProcessors(array &$content): array
    {
        $processors = [];
        foreach ($content['imports'] as $index => $import) {
            $processor = $this->getApplicableProcessor($import);
            if ($processor) {
                unset($content['imports'][$index]);
                $processors[] = $processor;
            }
        }

        return $processors;
    }

    /**
     * @param mixed $import
     * @return ConfigImportProcessorInterface|null
     */
    private function getApplicableProcessor($import)
    {
        foreach ($this->importProcessorFactories as $factory) {
            if ($factory->isApplicable($import)) {
                return $factory->create($import);
            }
        }

        throw new WorkflowConfigurationImportException(
            sprintf('Unknown config import directive. Given options: %s', var_export($import, 1))
        );
    }

    /**
     * @param ImportProcessorFactoryInterface $importProcessorGenerator
     */
    public function addImportProcessorFactory(ImportProcessorFactoryInterface $importProcessorGenerator)
    {
        $this->importProcessorFactories[] = $importProcessorGenerator;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     */
    public function setParent(ConfigImportProcessorInterface $parentProcessor)
    {
        throw new \LogicException('Main processor can not have parent.');
    }
}
