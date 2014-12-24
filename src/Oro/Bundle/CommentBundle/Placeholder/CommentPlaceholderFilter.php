<?php

namespace Oro\Bundle\CommentBundle\Placeholder;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class CommentPlaceholderFilter
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Checks if the entity can have comments
     *
     * @param object|null $entity
     * @return bool
     */
    public function isApplicable($entity = null)
    {
        if (!is_object($entity)) {
            return false;
        }

        $className = ClassUtils::getRealClass($entity);
        $config = $this->configManager->getProvider('comment')->getConfig($className);

        return $config->is('enabled');
    }
}
