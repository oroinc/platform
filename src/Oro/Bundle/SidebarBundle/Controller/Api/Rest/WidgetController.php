<?php

namespace Oro\Bundle\SidebarBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SidebarBundle\Entity\AbstractWidget;
use Oro\Bundle\SidebarBundle\Entity\Repository\WidgetRepository;

/**
 * @RouteResource("sidebarwidgets")
 * @NamePrefix("oro_api_")
 */
class WidgetController extends FOSRestController
{
    const SIDEBAR_WIDGET_FEATURE_NAME = 'sidebar_widgets';

    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get all sidebar widget items",
     *      resource=true
     * )
     * @param string $placement
     * @return Response
     */
    public function cgetAction($placement)
    {
        $token = $this->container->get('security.token_storage')->getToken();
        $organization = null;
        $items = [];
        if ($token instanceof OrganizationContextTokenInterface) {
            $organization = $token->getOrganizationContext();
            $items = $this->getRepository()->getWidgets($this->getUser(), $placement, $organization);
            $featureChecker = $this->get('oro_featuretoggle.checker.feature_checker');
            $items = array_filter(
                $items,
                function ($item) use ($featureChecker) {
                    if (!isset($item['widgetName'])) {
                        return false;
                    }

                    return $featureChecker->isResourceEnabled($item['widgetName'], self::SIDEBAR_WIDGET_FEATURE_NAME);
                }
            );
        }

        if (!$items) {
            $items = [];
        }

        return $this->handleView(
            $this->view($items, Codes::HTTP_OK)
        );
    }

    /**
     * REST POST
     *
     * @ApiDoc(
     *  description="Add Sidebar Widget",
     *  resource=true
     * )
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request)
    {
        $widgetClass = $this->getWidgetClass();
        /** @var AbstractWidget $entity */
        $entity = new $widgetClass();
        $entity->setWidgetName($request->get('widgetName'));
        $entity->setPosition($request->get('position'));
        $entity->setSettings($request->get('settings'));
        $entity->setPlacement($request->get('placement'));
        $entity->setState($request->get('state'));
        $entity->setUser($this->getUser());

        $token = $this->container->get('security.token_storage')->getToken();
        if ($token instanceof OrganizationContextTokenInterface) {
            $entity->setOrganization($token->getOrganizationContext());
        }

        $manager = $this->getManager();
        $manager->persist($entity);
        $manager->flush();

        return $this->handleView(
            $this->view(['id' => $entity->getId()], Codes::HTTP_CREATED)
        );
    }

    /**
     * REST PUT
     *
     * @param int $widgetId Widget instance id
     *
     * @param Request $request
     * @return Response
     * @ApiDoc(
     *  description="Update Sidebar Widget",
     *  resource=true
     * )
     */
    public function putAction($widgetId, Request $request)
    {
        /** @var \Oro\Bundle\SidebarBundle\Entity\Widget $entity */
        $entity = $this->getManager()->find($this->getWidgetClass(), (int)$widgetId);
        if (!$entity) {
            return $this->handleView($this->view([], Codes::HTTP_NOT_FOUND));
        }
        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view(null, Codes::HTTP_FORBIDDEN));
        }

        $entity->setState($request->get('state', $entity->getState()));
        $entity->setPosition($request->get('position', $entity->getPosition()));
        $entity->setSettings($request->get('settings', $entity->getSettings()));
        $entity->setPlacement($request->get('placement', $entity->getPlacement()));

        $em = $this->getManager();
        $em->persist($entity);
        $em->flush();

        return $this->handleView($this->view([], Codes::HTTP_OK));
    }

    /**
     * REST DELETE
     *
     * @param int $widgetId
     *
     * @ApiDoc(
     *  description="Remove Sidebar Widget instance",
     *  resource=true
     * )
     * @return Response
     */
    public function deleteAction($widgetId)
    {
        /** @var \Oro\Bundle\SidebarBundle\Entity\Widget $entity */
        $entity = $this->getManager()->find($this->getWidgetClass(), (int)$widgetId);
        if (!$entity) {
            return $this->handleView($this->view([], Codes::HTTP_NOT_FOUND));
        }
        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view(null, Codes::HTTP_FORBIDDEN));
        }

        $em = $this->getManager();
        $em->remove($entity);
        $em->flush();

        return $this->handleView($this->view([], Codes::HTTP_NO_CONTENT));
    }

    /**
     * Validate permissions
     *
     * @param UserInterface $user
     * @return bool
     */
    protected function validatePermissions(UserInterface $user)
    {
        return $user->getUsername() === $this->getUser()->getUsername();
    }

    /**
     * Get entity Manager
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getManager()
    {
        return $this->getDoctrine()->getManagerForClass($this->getWidgetClass());
    }

    /**
     * @return WidgetRepository
     */
    protected function getRepository()
    {
        return $this->getManager()->getRepository($this->getWidgetClass());
    }

    /**
     * @return string
     */
    protected function getWidgetClass()
    {
        return $this->getParameter('oro_sidebar.entity.widget.class');
    }
}
