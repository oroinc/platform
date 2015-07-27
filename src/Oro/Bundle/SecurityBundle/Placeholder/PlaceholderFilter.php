<?php

namespace Oro\Bundle\SecurityBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class PlaceholderFilter
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var ObjectManager */
    protected $manager;

    /**
     * @param SecurityFacade  $securityFacade
     * @param ConfigProvider  $configProvider
     * @param ObjectManager $manager
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ConfigProvider $configProvider,
        ObjectManager $manager
    ) {
        $this->securityFacade = $securityFacade;
        $this->configProvider = $configProvider;
        $this->manager        = $manager;
    }

    /**
     * Checks if the entity can be shared
     *
     * @param object $entity
     * @return bool
     */
    public function isShareEnabled($entity)
    {
        if (null === $entity || !is_object($entity)) {
            return false;
        }

        $className = ClassUtils::getClass($entity);
        return $this->securityFacade->isGranted('SHARE', $entity) && $this->configProvider->hasConfig($className) &&
            $this->configProvider->getConfig($className)->get('share_scopes');
    }

    /**
     * Check if the entity is shared
     *
     * @param object $entity
     * @return bool
     */
    public function isShared($entity)
    {
        return $this->isShareEnabled($entity) &&
            $this->manager->getRepository('OroSecurityBundle:AclEntry')->isEntityShared($entity);
    }
}
