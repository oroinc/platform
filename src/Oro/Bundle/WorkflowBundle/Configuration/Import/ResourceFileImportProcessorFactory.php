<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowListConfiguration;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Produces instance of import processor that corresponds to specific import section matched in config.
 */
class ResourceFileImportProcessorFactory implements ImportProcessorFactoryInterface
{
    /** @var ConfigFileReaderInterface */
    private $reader;

    /** @var array */
    private $kernelBundles;

    /**
     * @param ConfigFileReaderInterface $reader
     * @param array $kernelBundles
     */
    public function __construct(ConfigFileReaderInterface $reader, array $kernelBundles)
    {
        $this->reader = $reader;
        $this->kernelBundles = $kernelBundles;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($import): bool
    {
        return (bool)$this->getPath($import);
    }

    /**
     * @param mixed $import
     * @return string
     */
    private function getPath($import): string
    {
        $import = (array)$import;
        if (count($import) === 1 || count($import) === 2) {
            if (!ArrayUtil::isAssoc($import)) {
                return (string)reset($import);
            }

            if (isset($import['resource'])) {
                return (string)$import['resource'];
            }
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function create($import): ConfigImportProcessorInterface
    {
        if (!$this->isApplicable($import)) {
            throw new \InvalidArgumentException('Import options is not applicable for factory.');
        }

        return new ResourceFileImportProcessor(
            $this->reader,
            $this->getPath($import),
            $this->kernelBundles,
            $this->ignoreErrors($import)
        );
    }

    /**
     * @param $import
     * @return bool
     */
    private function ignoreErrors($import)
    {
        if (isset($import['ignore_errors'])) {
            return (bool)$import['ignore_errors'];
        }

        return false;
    }
}
