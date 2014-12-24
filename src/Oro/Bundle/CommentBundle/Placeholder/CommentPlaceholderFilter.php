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
     *
     * @return bool
     */
    public function isApplicable($entity = null)
    {
        if (!is_object($entity)) {
            return false;
        }

        $className = ClassUtils::getRealClass($entity);

        if (!$this->isExtendEntity($className)) {
            return false;
        }

        $config = $this->configManager->getProvider('comment')->getConfig($className);

        return $config->is('enabled');
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isExtendEntity($className)
    {
        $result = false;

        if ($this->configManager->hasConfig($className)) {
            $extendProvider = $this->configManager->getProvider('extend');

            $result = $extendProvider->getConfig($className)->is('is_extend');
        }

        return $result;
    }
}
