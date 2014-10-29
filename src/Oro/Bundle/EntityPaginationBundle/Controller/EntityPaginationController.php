<?php

namespace Oro\Bundle\EntityPaginationBundle\Controller;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;
use Oro\Bundle\EntityPaginationBundle\Storage\NavigationResult;
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
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationStorage::FIRST);
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
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationStorage::PREVIOUS);
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
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationStorage::NEXT);
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
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationStorage::LAST);
    }

    /**
     * @return DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        return $this->get('oro_entity.doctrine_helper');
    }

    /**
     * @return EntityPaginationStorage
     */
    protected function getStorage()
    {
        return $this->get('oro_entity_pagination.storage');
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
            case EntityPaginationStorage::FIRST:
                $result = $this->getStorage()->getFirstIdentifier($entity, $scope);
                break;
            case EntityPaginationStorage::PREVIOUS:
                $result = $this->getStorage()->getPreviousIdentifier($entity, $scope);
                break;
            case EntityPaginationStorage::NEXT:
                $result = $this->getStorage()->getNextIdentifier($entity, $scope);
                break;
            case EntityPaginationStorage::LAST:
                $result = $this->getStorage()->getLastIdentifier($entity, $scope);
                break;
        }

        if ($result instanceof NavigationResult) {
            $entityId = $result->getId();
            if ($entityId) {
                $params[$identifier] = $entityId;
            }

            if (!$result->isAvailable()) {
                $this->get('session')->getFlashBag()->add(
                    'alert',
                    'Entity is not available!'
                );
            } elseif (!$result->isAccessible()) {
                $this->get('session')->getFlashBag()->add(
                    'alert',
                    'Entity is not accessible!'
                );
            }

            return $this->redirect(
                $this->generateUrl($routeName, $params)
            );
        }
    }
}
