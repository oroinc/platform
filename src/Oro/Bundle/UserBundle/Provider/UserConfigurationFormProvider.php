<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;

class UserConfigurationFormProvider extends SystemConfigurationFormProvider
{
    const USER_TREE_NAME  = 'user_configuration';

    /**
     * {@inheritdoc}
     */
    public function getTree()
    {
        return $this->getTreeData(self::USER_TREE_NAME, self::CORRECT_FIELDS_NESTING_LEVEL);
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentCheckboxLabel()
    {
        return 'oro.user.user_configuration.use_default';
    }
}
