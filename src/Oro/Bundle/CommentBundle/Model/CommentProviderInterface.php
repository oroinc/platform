<?php

namespace Oro\Bundle\CommentBundle\Model;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

interface CommentProviderInterface
{
    /**
     * Returns true if entity has comment option true
     *
     * @param string        $entityName
     * @param ConfigManager $configManager
     *
     * @return bool
     */
    public function hasComments(ConfigManager $configManager, $entityName);
}
