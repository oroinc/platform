<?php

namespace Oro\Bundle\SecurityBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class PlaceholderFilter
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param SecurityFacade  $securityFacade
     * @param ConfigProvider  $configProvider
     * @param ManagerRegistry $registry
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ConfigProvider $configProvider,
        ManagerRegistry $registry
    ) {
        $this->securityFacade = $securityFacade;
        $this->configProvider = $configProvider;
        $this->registry       = $registry;
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

    public function isShared($entity)
    {
        if (null === $entity || !is_object($entity)) {
            return false;
        }

        $className = ClassUtils::getClass($entity);
        return $this->securityFacade->isGranted('SHARE', $entity) && $this->configProvider->hasConfig($className) &&
            $this->configProvider->getConfig($className)->get('share_scopes') &&
            $this->registry->getManager()->getRepository('OroSecurityBundle:AclEntry')->isEntityShared($entity);
    }
}
