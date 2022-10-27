<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Processor for import specific file as part of the configuration and add it by merging imported data over existed
 * Example:
 * ```YAML
 * imports:
 *     - { resource: './part1.yml', ignore_errors: false } #these file would be read and merged over existed content.
 * some_config_nodes: ...
 * ```
 */
class ResourceFileImportProcessor implements ConfigImportProcessorInterface
{
    /** @var ConfigFileReaderInterface */
    private $reader;

    /** @var string */
    private $importResource;

    /** @var FileLocatorInterface */
    private $fileLocator;

    /** @var bool */
    private $ignoreErrors = false;

    /** @var ConfigImportProcessorInterface */
    private $parent;

    /**
     * @param ConfigFileReaderInterface $reader
     * @param string $relativeFileResource Relative to $contentSource or absolute path.
     * @param FileLocatorInterface $fileLocator
     * @param bool $ignoreErrors
     */
    public function __construct(
        ConfigFileReaderInterface $reader,
        string $relativeFileResource,
        FileLocatorInterface $fileLocator,
        $ignoreErrors = false
    ) {
        $this->reader = $reader;
        $this->importResource = $relativeFileResource;
        $this->fileLocator = $fileLocator;
        $this->ignoreErrors = $ignoreErrors;
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $content, \SplFileInfo $contentSource): array
    {
        $importFile = $this->getImportFile($contentSource);
        if ($this->ignoreErrors === true && !$importFile->isReadable()) {
            return $content;
        }

        $importContent = $this->reader->read($importFile);

        if ($this->parent) {
            $importContent = $this->parent->process($importContent, $importFile);
        }

        return ArrayUtil::arrayMergeRecursiveDistinct($content, $importContent);
    }

    /**
     * {@inheritdoc}
     */
    public function setParent(ConfigImportProcessorInterface $parentProcessor)
    {
        $this->parent = $parentProcessor;
    }

    private function getImportFile(\SplFileInfo $contentSource): \SplFileInfo
    {
        if ('@' === $this->importResource[0]) {
            $fileName = $this->fileLocator->locate($this->importResource);
        } else {
            $fileName = $contentSource->getPath() . DIRECTORY_SEPARATOR . $this->importResource;
        }

        return new \SplFileInfo($fileName);
    }
}
