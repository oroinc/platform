<?php

namespace Oro\Bundle\EntityPaginationBundle\Controller;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation;
use Oro\Bundle\EntityPaginationBundle\Navigation\NavigationResult;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EntityPaginationController extends Controller
{
    /**
     * @Route(
     *    "/first/{entityName}/{scope}/{routeName}",
     *    name="oro_entity_pagination_first"
     * )
     *
     * @param $entityName
     * @param $scope
     * @param $routeName
     * @return RedirectResponse
     */
    public function firstAction($entityName, $scope, $routeName)
    {
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationNavigation::FIRST);
    }

    /**
     * @Route(
     *    "/previous/{entityName}/{scope}/{routeName}",
     *    name="oro_entity_pagination_previous"
     * )
     *
     * @param $entityName
     * @param $scope
     * @param $routeName
     * @return RedirectResponse
     */
    public function previousAction($entityName, $scope, $routeName)
    {
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationNavigation::PREVIOUS);
    }

    /**
     * @Route(
     *    "/next/{entityName}/{scope}/{routeName}",
     *    name="oro_entity_pagination_next"
     * )
     *
     * @param $entityName
     * @param $scope
     * @param $routeName
     * @return RedirectResponse
     */
    public function nextAction($entityName, $scope, $routeName)
    {
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationNavigation::NEXT);
    }

    /**
     * @Route(
     *    "/last/{entityName}/{scope}/{routeName}",
     *    name="oro_entity_pagination_last"
     * )
     *
     * @param $entityName
     * @param $scope
     * @param $routeName
     * @return RedirectResponse
     */
    public function lastAction($entityName, $scope, $routeName)
    {
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationNavigation::LAST);
    }

    /**
     * @return DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        return $this->get('oro_entity.doctrine_helper');
    }

    /**
     * @return EntityPaginationNavigation
     */
    protected function getNavigation()
    {
        return $this->get('oro_entity_pagination.navigation');
    }

    /**
     * @param string $entityName
     * @param string $scope
     * @param string $routeName
     * @param string $navigation
     * @return RedirectResponse
     */
    protected function getLink($entityName, $scope, $routeName, $navigation)
    {
        $params          = $this->getRequest()->query->all();
        $identifier      = $this->getDoctrineHelper()->getSingleEntityIdentifierFieldName($entityName);
        $identifierValue = $params[$identifier];
        $entity          = $this->getDoctrineHelper()->getEntityReference($entityName, $identifierValue);

        switch ($navigation) {
            case EntityPaginationNavigation::FIRST:
                $result = $this->getNavigation()->getFirstIdentifier($entity, $scope);
                break;
            case EntityPaginationNavigation::PREVIOUS:
                $result = $this->getNavigation()->getPreviousIdentifier($entity, $scope);
                break;
            case EntityPaginationNavigation::NEXT:
                $result = $this->getNavigation()->getNextIdentifier($entity, $scope);
                break;
            case EntityPaginationNavigation::LAST:
                $result = $this->getNavigation()->getLastIdentifier($entity, $scope);
                break;
        }

        /** @var NavigationResult $result */
        if ($result instanceof NavigationResult) {
            $entityId = $result->getId();
            if ($entityId) {
                $params[$identifier] = $entityId;
            }

            if (!$result->isAvailable()) {
                $this->get('session')->getFlashBag()->add(
                    'alert',
                    $this->get('translator')->trans('oro.entity_pagination.not_available')
                );
            } elseif (!$result->isAccessible()) {
                $this->get('session')->getFlashBag()->add(
                    'alert',
                    $this->get('translator')->trans('oro.entity_pagination.not_accessible')
                );
            }
        }

        return $this->redirect(
            $this->generateUrl($routeName, $params)
        );
    }
}
