<?php

namespace Oro\Bundle\EntityPaginationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation;
use Oro\Bundle\EntityPaginationBundle\Navigation\NavigationResult;

class EntityPaginationController extends Controller
{
    /**
     * @Route("/first/{entityName}/{scope}/{routeName}", name="oro_entity_pagination_first")
     *
     * @param $entityName
     * @param $scope
     * @param $routeName
     * @return JsonResponse
     */
    public function firstAction($entityName, $scope, $routeName)
    {
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationNavigation::FIRST);
    }

    /**
     * @Route("/previous/{entityName}/{scope}/{routeName}", name="oro_entity_pagination_previous")
     *
     * @param $entityName
     * @param $scope
     * @param $routeName
     * @return JsonResponse
     */
    public function previousAction($entityName, $scope, $routeName)
    {
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationNavigation::PREVIOUS);
    }

    /**
     * @Route("/next/{entityName}/{scope}/{routeName}", name="oro_entity_pagination_next")
     *
     * @param $entityName
     * @param $scope
     * @param $routeName
     * @return JsonResponse
     */
    public function nextAction($entityName, $scope, $routeName)
    {
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationNavigation::NEXT);
    }

    /**
     * @Route("/last/{entityName}/{scope}/{routeName}", name="oro_entity_pagination_last")
     *
     * @param $entityName
     * @param $scope
     * @param $routeName
     * @return JsonResponse
     */
    public function lastAction($entityName, $scope, $routeName)
    {
        return $this->getLink($entityName, $scope, $routeName, EntityPaginationNavigation::LAST);
    }

    /**
     * @param string $entityName
     * @param string $scope
     * @param string $routeName
     * @param string $navigation
     * @return JsonResponse
     */
    protected function getLink($entityName, $scope, $routeName, $navigation)
    {
        $doctrineHelper = $this->get('oro_entity.doctrine_helper');
        $navigationService = $this->get('oro_entity_pagination.navigation');

        $params = $this->getRequest()->query->all();
        $entityName = $this->get('oro_entity.routing_helper')->decodeClassName($entityName);
        $identifier = $doctrineHelper->getSingleEntityIdentifierFieldName($entityName);
        $message = null;

        if (!empty($params[$identifier])) {
            $identifierValue = $params[$identifier];
            $entity = $doctrineHelper->getEntityReference($entityName, $identifierValue);

            switch ($navigation) {
                case EntityPaginationNavigation::FIRST:
                    $result = $navigationService->getFirstIdentifier($entity, $scope);
                    break;
                case EntityPaginationNavigation::PREVIOUS:
                    $result = $navigationService->getPreviousIdentifier($entity, $scope);
                    break;
                case EntityPaginationNavigation::NEXT:
                    $result = $navigationService->getNextIdentifier($entity, $scope);
                    break;
                case EntityPaginationNavigation::LAST:
                    $result = $navigationService->getLastIdentifier($entity, $scope);
                    break;
            }

            /** @var NavigationResult $result */
            if ($result instanceof NavigationResult) {
                $entityId = $result->getId();
                if ($entityId) {
                    $params[$identifier] = $entityId;
                }

                $messageManager = $this->get('oro_entity_pagination.message_manager');

                if (!$result->isAvailable()) {
                    $message = $messageManager->getNotAvailableMessage($entity, $scope);
                } elseif (!$result->isAccessible()) {
                    $message = $messageManager->getNotAccessibleMessage($entity, $scope);
                }
            }
        }

        $url = $this->generateUrl($routeName, $params);

        return new JsonResponse(['url' => $url, 'message' => $message]);
    }
}
