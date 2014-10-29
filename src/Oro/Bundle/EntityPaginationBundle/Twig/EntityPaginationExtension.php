<?php

namespace Oro\Bundle\EntityPaginationBundle\Twig;

use Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;
use Doctrine\Common\Util\ClassUtils;

class EntityPaginationExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_pagination';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EntityPaginationNavigation
     */
    protected $paginationNavigation;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityPaginationNavigation $paginationNavigation
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntityPaginationNavigation $paginationNavigation)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->paginationNavigation = $paginationNavigation;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_entity_pagination_pager', [$this, 'getPager']),
            new \Twig_SimpleFunction(
                'oro_entity_pagination_name',
                function ($entity) {
                    return ClassUtils::getClass($entity);
                }
            ),
        );
    }

    /**
     * Null - pager data is not accessible
     * Array('total' => <int>, 'current' => <int>) - used to generate string "<current> of <total>"
     *
     * @param object $entity
     * @return null|array
     */
    public function getPager($entity)
    {
        $totalCount = $this->paginationNavigation->getTotalCount($entity);
        if (!$totalCount) {
            return null;
        }

        $currentNumber = $this->paginationNavigation->getCurrentNumber($entity);
        if (!$currentNumber) {
            return null;
        }

        return ['total' => $totalCount, 'current' => $currentNumber];
    }

    /**
     * @return null|array
     */
    protected function getRouteAndParameters()
    {
        if (!$this->request) {
            return null;
        }

        $route = $this->request->attributes->get('_route');
        if (!$route) {
            return null;
        }

        $routeParameters = $this->request->attributes->get('_route_params');
        // at least entity identifier parameter must be specified
        if (!$routeParameters) {
            return null;
        }

        return ['route' => $route, 'route_params' => $routeParameters];
    }

    /**
     * @param array $routeAndParameters
     * @param object $entity
     * @param int|string $entityId
     * @return null|array
     */
    protected function addEntityIdParameter(array $routeAndParameters, $entity, $entityId)
    {
        $fieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entity);
        if (!$fieldName) {
            return null;
        }

        // no entity identifier parameter
        if (!isset($routeAndParameters['route_params'][$fieldName])) {
            return null;
        }

        $routeAndParameters['route_params'][$fieldName] = $entityId;

        return $routeAndParameters;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
