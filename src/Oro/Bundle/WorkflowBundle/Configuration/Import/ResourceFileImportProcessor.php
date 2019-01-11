<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Oro\Component\PhpUtils\ArrayUtil;

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

    /** @var array */
    private $kernelBundles;

    /** @var bool */
    private $ignoreErrors = false;

    /** @var ConfigImportProcessorInterface */
    private $parent;

    /**
     * @param ConfigFileReaderInterface $reader
     * @param string $relativeFileResource Relative to $contentSource or absolute path.
     * @param array $kernelBundles
     * @param bool $ignoreErrors
     */
    public function __construct(
        ConfigFileReaderInterface $reader,
        string $relativeFileResource,
        array $kernelBundles,
        $ignoreErrors = false
    ) {
        $this->reader = $reader;
        $this->importResource = $relativeFileResource;
        $this->kernelBundles = $kernelBundles;
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

    /**
     * @param \SplFileInfo
     *
     * @return \SplFileInfo
     */
    private function getImportFile(\SplFileInfo $contentSource)
    {
        $fileName = $contentSource->getPath() . DIRECTORY_SEPARATOR . $this->importResource;

        if ('@' === $this->importResource[0]) {
            $bundleName = substr($this->importResource, 1);
            $path = '';
            if (false !== strpos($bundleName, '/')) {
                list($bundleName, $path) = explode('/', $bundleName, 2);
            }

            foreach ($this->kernelBundles as $bundle) {
                if (strpos($bundle, $bundleName) !== false) {
                    $reflection = new \ReflectionClass($bundle);
                    $bundleConfigDirectory = dirname($reflection->getFileName());
                    $fileName = $bundleConfigDirectory . DIRECTORY_SEPARATOR . $path;
                }
            }
        }

        return new \SplFileInfo($fileName);
    }
}
