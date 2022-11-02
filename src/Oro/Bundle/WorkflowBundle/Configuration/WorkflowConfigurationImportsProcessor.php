<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\Import\ImportProcessorFactoryInterface;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowConfigurationImportException;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Main entry point of processing workflow imports.
 * Processes each import and merges them one into another. The existing config is merged onto the resulting imported
 * config. For example:
 *
 *   imports:
 *     - { resource: 'file1.yml', workflow: workflow1, as: workflow3, replace: [] } # import1
 *     - { workflow: workflow2, as: workflow3, replace: [] }                        # import2
 *
 *   workflows:                                                                     # existing config
 *     workflow3:
 *       entity: \stdClass
 *
 * will be imported in the following way:
 * 1. Process and get config from "import1"
 * 2. Process, get config from "import2" and merge it onto config got in p.1
 * 3. Merge "existing config" onto config got in p.2
 */
class WorkflowConfigurationImportsProcessor implements ConfigImportProcessorInterface
{
    /** @var ImportProcessorFactoryInterface[] */
    protected $importProcessorFactories = [];

    /** @var array */
    protected $processedContent = [];

    /** {@inheritdoc} */
    public function process(array $content, \SplFileInfo $contentSource): array
    {
        if (empty($content['imports']) || !is_array($content['imports'])) {
            return $content;
        }

        $filePath = $contentSource->getRealPath();

        if (isset($this->processedContent[$filePath])) {
            return $this->processedContent[$filePath];
        }

        $importProcessors = $this->getImportProcessors($content['imports']);
        unset($content['imports']);
        $importedContent = [];

        foreach ($importProcessors as $processor) {
            $processor->setParent($this);

            // Content from partially processed imports is needed in case of recursive import.
            $this->processedContent[$filePath] = ArrayUtil::arrayMergeRecursiveDistinct($importedContent, $content);

            $importedContent = $processor->process($importedContent, $contentSource);
        }

        return $this->processedContent[$filePath] = ArrayUtil::arrayMergeRecursiveDistinct($importedContent, $content);
    }

    /**
     * @param array $imports
     * @return iterable<ConfigImportProcessorInterface>
     */
    private function getImportProcessors(array $imports): iterable
    {
        foreach ($imports as $import) {
            if ($processor = $this->getApplicableProcessor($import)) {
                yield $processor;
            }
        }

        return [];
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
