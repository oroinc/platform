<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Reader;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;

class YamlFileCachedReader implements ConfigFileReaderInterface
{
    /** @var array */
    private $loadedFiles = [];

    /**
     * {@inheritdoc}
     */
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
