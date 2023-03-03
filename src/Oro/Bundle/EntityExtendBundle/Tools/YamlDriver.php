<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\ORM\Mapping\Driver\YamlDriver as BaseYamlDriver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\Locator\ExtendEntityLocator;

/**
 * Extend entity orm mapping driver to load metadata from the cache instead of YAML files
 */
class YamlDriver extends BaseYamlDriver
{
    private array $schemas = [];

    public function __construct(private Configmanager $configManager)
    {
        parent::__construct(new ExtendEntityLocator($configManager), null);
    }

    /**
     * {@inheritDoc}
     * $file === 'ClassName'
     */
    protected function loadMappingFile($file)
    {
        if (empty($this->schemas)) {
            $this->initializeSchemas();
        }

        return [
            $file => isset($this->schemas[$file])
                ? array_shift($this->schemas[$file])
                : []
        ];
    }

    private function initializeSchemas(): void
    {
        $extendConfigs = $this->configManager->getProvider('extend')->getConfigs(null, true);
        foreach ($extendConfigs as $extendConfig) {
            $schema = $extendConfig->get('schema');
            $className = $extendConfig->getId()->getClassName();
            if (isset($schema['doctrine'])) {
                $this->schemas[$className] = $schema['doctrine'];
            }
        }
    }
}
