<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;

/**
 * Processor for import parts of workflows from specific file.
 * Example (workflows.yml):
 * ```
 * imports:
 *     - { resource: './workflow_parts.yml', workflow: "to_import", as: "to_accept", replace: [] }
 * workflows:
 *     to_accept:
 *         entity: Entity1
 *         transitions:
 *             transition1: { is_start: true }
 * ```
 * Content of 'workflow_parts.yml':
 * ```
 * workflows:
 *     to_import:
 *         steps:
 *             step_a: ~
 * ```
 * Result of (workflows.yml) processing:
 * ```
 * workflows:
 *     to_accept:
 *         entity: Entity1
 *         steps:
 *             step_a: ~
 *         transitions:
 *             transition1: { is_start: true }
 * ```
 * Steps has been imported.
 *
 * @see WorkflowFileImportProcessorFactory for properties assignments.
 */
class WorkflowFileImportProcessor implements ConfigImportProcessorInterface
{
    use WorkflowImportTrait;

    /** @var ConfigFileReaderInterface */
    private $reader;

    /** @var ConfigImportProcessorInterface */
    private $parent;

    /** @var string */
    private $file;

    /**
     * @param ConfigFileReaderInterface $reader
     * @param string $file relative to current context file path
     */
    public function __construct(ConfigFileReaderInterface $reader, string $file)
    {
        $this->reader = $reader;
        $this->file = $file;
    }

    /** {@inheritdoc} */
    public function process(array $content, \SplFileInfo $contentSource): array
    {
        $importFile = new \SplFileInfo($contentSource->getPath() . DIRECTORY_SEPARATOR . $this->file);
        $importContent = $this->reader->read($importFile);

        if ($this->parent) {
            $importContent = $this->parent->process($importContent, $importFile);
        }

        if ($this->isResourcePresent($importContent)) {
            $resourceData = $this->getResourceData($importContent);

            $resourceData = $this->applyReplacements($resourceData);
            $content = $this->mergeConfigs($resourceData, $content);
        }

        return $content;
    }

    /** {@inheritdoc} */
    public function setParent(ConfigImportProcessorInterface $parentProcessor)
    {
        $this->parent = $parentProcessor;
    }
}
