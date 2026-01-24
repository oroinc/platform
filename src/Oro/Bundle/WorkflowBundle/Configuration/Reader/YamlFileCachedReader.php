<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Reader;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads and caches YAML workflow configuration files.
 *
 * Implementation of {@see ConfigFileReaderInterface} that reads YAML configuration files and caches their parsed
 * content in memory. Caching prevents redundant file I/O and YAML parsing operations when the same configuration
 * file is accessed multiple times during workflow configuration loading. Validates file readability before
 * attempting to parse and provides meaningful error messages for unreadable files. This reader is the primary
 * mechanism for loading workflow definitions from YAML files in the configuration system.
 */
class YamlFileCachedReader implements ConfigFileReaderInterface
{
    /** @var array */
    private $loadedFiles = [];

    #[\Override]
    public function read(\SplFileInfo $file): array
    {
        if (!$file->isReadable()) {
            throw new InvalidConfigurationException(
                sprintf('Resource "%s" is unreadable', $file->getBasename())
            );
        }
        $realPathName = $file->getRealPath();
        if (isset($this->loadedFiles[$realPathName])) {
            return $this->loadedFiles[$realPathName];
        }

        $content = Yaml::parse(file_get_contents($realPathName)) ?: [];

        return $this->loadedFiles[$realPathName] = $content;
    }
}
