<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\Locator;

use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Loads metadata from the cache instead of YAML files
 */
class ExtendEntityLocator implements FileLocator
{
    private array $classNames = [];

    public function __construct(private Configmanager $configManager)
    {
        $this->initialize();
    }

    protected function initialize(): void
    {
        if (!empty($this->classNames)) {
            return;
        }
        $extendConfigs = $this->configManager->getProvider('extend')->getConfigs(null, true);
        foreach ($extendConfigs as $extendConfig) {
            if ($extendConfig->get('schema')) {
                $this->classNames[] = $extendConfig->getId()->getClassName();
            }
        }
    }

    #[\Override]
    public function findMappingFile($className)
    {
        return $className;
    }

    #[\Override]
    public function getAllClassNames($globalBasename)
    {
        return $this->classNames;
    }

    #[\Override]
    public function fileExists($className)
    {
        return true;
    }

    #[\Override]
    public function getPaths()
    {
        return [];
    }

    #[\Override]
    public function getFileExtension()
    {
        return '';
    }
}
