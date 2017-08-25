<?php

namespace Oro\Bundle\ConfigBundle\Provider;

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
}
