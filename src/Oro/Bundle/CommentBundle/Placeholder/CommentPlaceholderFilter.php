<?php

namespace Oro\Bundle\CommentBundle\Placeholder;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CommentPlaceholderFilter
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ConfigManager     $configManager
     * @param SecurityFacade    $securityFacade
     */
    public function __construct(ConfigManager $configManager, SecurityFacade $securityFacade)
    {
        $this->configManager  = $configManager;
        $this->securityFacade = $securityFacade;
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
        if (!is_object($entity) || !$this->securityFacade->isGranted('oro_comment_view')) {
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
