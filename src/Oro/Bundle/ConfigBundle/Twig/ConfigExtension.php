<?php

namespace Oro\Bundle\ConfigBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager as GlobalConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class ConfigExtension extends \Twig_Extension
{
    /** @var ConfigManager */
    protected $cm;

    /** ConfigManager **/
    protected $entityConfigManager;

    public function __construct(GlobalConfigManager $cm, ConfigManager $entityConfigManager)
    {
        $this->cm                  = $cm;
        $this->entityConfigManager = $entityConfigManager;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'oro_config_value'  => new \Twig_Function_Method($this, 'getConfigValue'),
            'oro_config_entity' => new \Twig_Function_Method($this, 'getEntityOutput'),
        );
    }

    /**
     * @param  string $name Setting name in "{bundle}.{setting}" format
     *
     * @return mixed
     */
    public function getConfigValue($name)
    {
        return $this->cm->get($name);
    }

    /**
     * Get entity output config (if any provided).
     * Provided parameters:
     *  "icon_class"  - CSS class name for icon element
     *  "name"        - custom entity name
     *  "description" - entity description
     *
     * @param  string $class FQCN of the entity
     *
     * @deprecated since 1.3 will be removed in 1.5 use "oro_entity_config" instead
     *
     * @return array
     */
    public function getEntityOutput($class)
    {
        $default = [
            'icon_class'  => '',
            'name'        => 'N/A',
            'description' => 'No description'
        ];

        if (!$this->entityConfigManager->hasConfig($class)) {
            return $default;
        }

        $entityConfig = new EntityConfigId('entity', $class);
        $configs      = $this->entityConfigManager->getConfig($entityConfig);

        return [
            'icon_class'  => $configs->get('icon'),
            'name'        => $configs->get('label'),
            'description' => $configs->get('description'),
        ];
    }

    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'config_extension';
    }
}
