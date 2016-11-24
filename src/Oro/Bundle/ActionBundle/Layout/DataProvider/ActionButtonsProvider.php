<?php

namespace Oro\Bundle\ActionBundle\Layout\DataProvider;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionButtonsProvider
{
    /** @var RouteProviderInterface */
    protected $routeProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param RouteProviderInterface $routeProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(RouteProviderInterface $routeProvider, DoctrineHelper $doctrineHelper)
    {
        $this->routeProvider = $routeProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return string
     */
    public function getDialogRoute()
    {
        return $this->routeProvider->getFormDialogRoute();
    }

    /**
     * @return string
     */
    public function getPageRoute()
    {
        return $this->routeProvider->getFormPageRoute();
    }

    /**
     * @return string
     */
    public function getExecutionRoute()
    {
        return $this->routeProvider->getExecutionRoute();
    }

    /**
     * @param object|string $entity
     *
     * @return string
     */
    public function getEntityClass($entity)
    {
        return is_object($entity) ? ClassUtils::getClass($entity) : ClassUtils::getRealClass($entity);
    }

    /**
     * @param object $entity
     *
     * @return int|null
     */
    public function getEntityId($entity)
    {
        return is_object($entity) ? $this->doctrineHelper->getSingleEntityIdentifier($entity) : null;
    }
}
