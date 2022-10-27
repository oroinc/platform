<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Processor for import parts of workflows from specific file.
 * Example (workflows.yml):
 * ```
 * imports:
 *     - { resource: './workflow_parts.yml', workflow: "to_import", as: "to_accept", replace: [] }
 *     - { resource: '@AcmeDemoBundle:workflows/workflow_name/workflow_parts.yml' }
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

    /** @var FileLocatorInterface */
    private $fileLocator;

    /**
     * @param ConfigFileReaderInterface $reader
     * @param string $file relative to current context file path
     * @param FileLocatorInterface $fileLocator
     */
    public function __construct(ConfigFileReaderInterface $reader, string $file, FileLocatorInterface $fileLocator)
    {
        $this->reader = $reader;
        $this->file = $file;
        $this->fileLocator = $fileLocator;
    }

    /** {@inheritdoc} */
    public function process(array $content, \SplFileInfo $contentSource): array
    {
        $importFile = $this->getImportFile($contentSource);
        $importContent = $this->reader->read($importFile);

        if ($this->parent) {
            $importContent = $this->parent->process($importContent, $importFile);
        }

        if ($this->isResourcePresent($importContent)) {
            $resourceData = $this->getResourceData($importContent);

            $resourceData = $this->applyReplacements($resourceData);
            $content = $this->mergeImports($content, $resourceData);
        }

        return $content;
    }

    /** {@inheritdoc} */
    public function setParent(ConfigImportProcessorInterface $parentProcessor)
    {
        $this->parent = $parentProcessor;
    }

    private function getImportFile(\SplFileInfo $contentSource): \SplFileInfo
    {
        if ('@' === $this->file[0]) {
            $fileName = $this->fileLocator->locate($this->file);
        } else {
            $fileName = $contentSource->getPath() . DIRECTORY_SEPARATOR . $this->file;
        }

        return new \SplFileInfo($fileName);
    }
}
