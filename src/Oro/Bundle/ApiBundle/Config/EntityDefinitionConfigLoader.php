<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class EntityDefinitionConfigLoader extends AbstractConfigLoader implements ConfigLoaderFactoryAwareInterface
{
    /** @var array */
    protected $methodMap = [
        EntityDefinitionConfig::POST_SERIALIZE => 'setPostSerializeHandler',
    ];

    /** @var ConfigLoaderFactory */
    protected $factory;

    /**
     * {@inheritdoc}
     */
    public function setConfigLoaderFactory(ConfigLoaderFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $definition = new EntityDefinitionConfig();
        $this->loadDefinition($definition, $config);

        return $definition;
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param array|null             $config
     */
    protected function loadDefinition(EntityDefinitionConfig $definition, array $config = null)
    {
        if (empty($config)) {
            return;
        }

        foreach ($config as $key => $value) {
            if (ConfigUtil::FIELDS === $key) {
                $this->loadFields($definition, $value);
            } elseif ($this->factory->hasLoader($key)) {
                $this->loadSection($definition, $this->factory->getLoader($key), $key, $value);
            } else {
                $this->loadConfigValue($definition, $key, $value, $this->methodMap);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param array|null             $fields
     */
    protected function loadFields(EntityDefinitionConfig $definition, array $fields = null)
    {
        if (!empty($fields)) {
            foreach ($fields as $name => $config) {
                $definition->addField(
                    $name,
                    $this->factory->getLoader(ConfigUtil::FIELDS)->load(null !== $config ? $config : [])
                );
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ConfigLoaderInterface  $loader
     * @param string                 $sectionName
     * @param array|null             $config
     */
    protected function loadSection(
        EntityDefinitionConfig $definition,
        ConfigLoaderInterface $loader,
        $sectionName,
        array $config = null
    ) {
        if (!empty($config)) {
            $section = $loader->load($config);
            $isEmpty = false;
            if (is_object($section)) {
                if (method_exists($section, 'isEmpty') && $section->isEmpty()) {
                    $isEmpty = true;
                }
            } elseif (empty($section)) {
                $isEmpty = true;
            }
            if (!$isEmpty) {
                $this->setValue($definition, $sectionName, $section);
            }
        }
    }
}
