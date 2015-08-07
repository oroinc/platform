<?php

namespace Oro\Bundle\SecurityBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class PlaceholderFilter
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param SecurityFacade  $securityFacade
     * @param ConfigProvider  $configProvider
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ConfigProvider $configProvider
    ) {
        $this->securityFacade = $securityFacade;
        $this->configProvider = $configProvider;
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
}
