<?php

namespace Oro\Bundle\EntityPaginationBundle\Twig;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;

class EntityPaginationExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_pagination';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EntityPaginationStorage
     */
    protected $storage;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityPaginationStorage $storage
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntityPaginationStorage $storage)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_entity_pagination_previous', [$this, 'getPrevious']),
            new \Twig_SimpleFunction('oro_entity_pagination_next', [$this, 'getNext']),
            new \Twig_SimpleFunction('oro_entity_pagination_pager', [$this, 'getPager']),
        ];
    }

    /**
     * Null - previous entity is not accessible
     * Array('route' => <string>, 'route_params' => <array>)
     *
     * @param object $entity
     * @return null|array
     */
    public function getPrevious($entity)
    {
        $routeAndParameters = $this->getRouteAndParameters();
        if (!$routeAndParameters) {
            return null;
        }

        $previousEntityId = $this->storage->getPreviousIdentifier($entity);
        if (!$previousEntityId) {
            return null;
        }

        return $this->addEntityIdParameter($routeAndParameters, $entity, $previousEntityId);
    }

    /**
     * Null - next entity is not accessible
     * Array('route' => <string>, 'route_params' => <array>)
     *
     * @param object $entity
     * @return null|string
     */
    public function getNext($entity)
    {
        $routeAndParameters = $this->getRouteAndParameters();
        if (!$routeAndParameters) {
            return null;
        }

        $nextEntityId = $this->storage->getNextIdentifier($entity);
        if (!$nextEntityId) {
            return null;
        }

        return $this->addEntityIdParameter($routeAndParameters, $entity, $nextEntityId);
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
        $totalCount = $this->storage->getTotalCount($entity);
        if (!$totalCount) {
            return null;
        }

        $currentNumber = $this->storage->getCurrentNumber($entity);
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
