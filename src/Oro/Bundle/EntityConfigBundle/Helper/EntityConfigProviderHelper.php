<?php

namespace Oro\Bundle\EntityConfigBundle\Helper;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

/**
 * Logic moved from ConfigController to enable it for attributes also
 */
class EntityConfigProviderHelper
{
    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Return configured layout actions and requirejs modules
     *
     * @param  EntityConfigModel $entity
     * @param null $displayOnly Param used to bind layout action with certain flag. If it is passed only actions
     * which has it will be showed
     * @return array
     */
    public function getLayoutParams(EntityConfigModel $entity, $displayOnly = null)
    {
        $actions = [];
        $requireJsModules = [];

        $providers = $this->configManager->getProviders();
        /** @var ConfigProvider $provider */
        foreach ($providers as $provider) {
            $propertyConfig = $provider->getPropertyConfig();
            $layoutActions = $propertyConfig->getLayoutActions(PropertyConfigContainer::TYPE_FIELD);
            foreach ($layoutActions as $action) {
                if ($this->isLayoutActionApplicable($action, $entity, $provider, $displayOnly)) {
                    if (isset($action['entity_id']) && $action['entity_id']) {
                        $action['args'] = ['id' => $entity->getId()];
                    }
                    $actions[] = $action;
                }
            }

            $requireJsModules = array_merge($requireJsModules, $propertyConfig->getRequireJsModules());
        }

        return [$actions, $requireJsModules];
    }

    /**
     * @param array $action
     * @param EntityConfigModel $entity
     * @param ConfigProvider $provider
     * @param null $displayOnly
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
        } elseif (!$displayOnly && isset($action['display_only'])) {
            return false;
        } elseif (!isset($action['filter'])) {
            return true;
        }

        return $this->applyFilter($action, $entity, $provider);
    }

    /**
     * @param array $action
     * @param EntityConfigModel $entity
     * @param ConfigProvider $provider
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
