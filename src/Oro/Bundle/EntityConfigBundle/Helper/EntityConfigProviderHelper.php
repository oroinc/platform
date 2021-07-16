<?php

namespace Oro\Bundle\EntityConfigBundle\Helper;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

/**
 * Provides a set of reusable methods for entity configs and attributes controllers.
 */
class EntityConfigProviderHelper
{
    /** @var ConfigManager */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Return configured layout actions and js modules
     *
     * @param EntityConfigModel $entity
     * @param string|null       $displayOnly It is used to bind layout action with certain flag.
     *                                       If it is passed only actions which has it will be showed.
     *
     * @return array
     */
    public function getLayoutParams(EntityConfigModel $entity, $displayOnly = null)
    {
        $actions = [];
        $jsModules = [];
        $providers = $this->configManager->getProviders();
        foreach ($providers as $provider) {
            $propertyConfig = $provider->getPropertyConfig();
            $providerActions = $propertyConfig->getLayoutActions(PropertyConfigContainer::TYPE_FIELD);
            foreach ($providerActions as $action) {
                if ($this->isLayoutActionApplicable($action, $entity, $provider, $displayOnly)) {
                    if (isset($action['entity_id']) && $action['entity_id']) {
                        $action['args'] = ['id' => $entity->getId()];
                    }
                    $actions[] = $action;
                }
            }
            $providerModules = $propertyConfig->getJsModules();
            foreach ($providerModules as $module) {
                $jsModules[] = $module;
            }
        }

        return [$actions, $jsModules];
    }

    /**
     * @param array             $action
     * @param EntityConfigModel $entity
     * @param ConfigProvider    $provider
     * @param null              $displayOnly
     *
     * @return bool
     */
    private function isLayoutActionApplicable(
        array $action,
        EntityConfigModel $entity,
        ConfigProvider $provider,
        $displayOnly = null
    ) {
        if ($displayOnly && (!isset($action['display_only']) || $action['display_only'] !== $displayOnly)) {
            return false;
        }
        if (!$displayOnly && isset($action['display_only'])) {
            return false;
        }
        if (!isset($action['filter'])) {
            return true;
        }

        return $this->applyFilter($action, $entity, $provider);
    }

    /**
     * @param array             $action
     * @param EntityConfigModel $entity
     * @param ConfigProvider    $provider
     *
     * @return bool
     */
    private function applyFilter(array $action, EntityConfigModel $entity, ConfigProvider $provider)
    {
        $result = true;
        foreach ($action['filter'] as $key => $value) {
            if ($key === 'mode') {
                if ($entity->getMode() !== $value) {
                    $result = false;
                    break;
                }
            } else {
                $config = $provider->getConfig($entity->getClassName());
                if (is_array($value)) {
                    if (!$config->in($key, $value)) {
                        $result = false;
                        break;
                    }
                } elseif ($config->get($key) != $value) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }
}
