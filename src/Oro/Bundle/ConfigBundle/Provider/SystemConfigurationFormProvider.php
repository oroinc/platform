<?php

namespace Oro\Bundle\ConfigBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsFormOptionsEvent;

/**
 * Provides data for configuration form on the system level.
 */
class SystemConfigurationFormProvider extends AbstractProvider
{
    const TREE_NAME = 'system_configuration';

    /**
     * {@inheritdoc}
     */
    public function getTree()
    {
        return $this->getTreeData(self::TREE_NAME, self::CORRECT_FIELDS_NESTING_LEVEL);
    }

    /**
     * {@inheritdoc}
     */
    public function getJsTree()
    {
        return $this->getJsTreeData(self::TREE_NAME, self::CORRECT_MENU_NESTING_LEVEL);
    }

    /**
     * Use default checkbox label
     *
     * @return string
     */
    protected function getParentCheckboxLabel()
    {
        return 'oro.config.system_configuration.use_default';
    }

    protected function dispatchConfigSettingFormOptionsEvent(array $formOptions): array
    {
        $event = new ConfigSettingsFormOptionsEvent(GlobalScopeManager::SCOPE_NAME, $formOptions);
        $this->eventDispatcher->dispatch($event, ConfigSettingsFormOptionsEvent::SET_OPTIONS);

        return $event->getAllFormOptions();
    }
}
