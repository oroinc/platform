<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;

/**
 * Processor for import specific file as part of the configuration and add it by merging imported data over existed
 * Example:
 * ```YAML
 * imports:
 *     - { resource: './part1.yml' } #these file would be read and merged over existed content.
 * some_config_nodes: ...
 * ```
 */
class ResourceFileImportProcessor implements ConfigImportProcessorInterface
{
    /** @var ConfigFileReaderInterface */
    private $reader;

    /** @var string */
    private $importResource;

    /** @var ConfigImportProcessorInterface */
    private $parent;

    /**
     * @param ConfigFileReaderInterface $reader
     * @param string $relativeFileResource Relative to $contentSource or absolute path.
     */
    public function __construct(ConfigFileReaderInterface $reader, string $relativeFileResource)
    {
        $this->reader = $reader;
        $this->importResource = $relativeFileResource;
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $content, \SplFileInfo $contentSource): array
    {
        $importFile = new \SplFileInfo($contentSource->getPath() . DIRECTORY_SEPARATOR . $this->importResource);

        $importContent = $this->reader->read($importFile);

        if ($this->parent) {
            $importContent = $this->parent->process($importContent, $importFile);
        }

        return array_merge_recursive($content, $importContent);
    }

    /**
     * {@inheritdoc}
     */
    public function setParent(ConfigImportProcessorInterface $parentProcessor)
    {
        $this->parent = $parentProcessor;
    }
}
