<?php

namespace Oro\Bundle\SidebarBundle\Controller\Api\Rest;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use Oro\Bundle\SidebarBundle\Entity\Repository\WidgetRepository;
use Oro\Bundle\SidebarBundle\Entity\Widget;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

/**
 * @RouteResource("sidebarwidgets")
 * @NamePrefix("oro_api_")
 */
class WidgetController extends FOSRestController
{
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
        /** @var WidgetRepository $widgetRepository */
        $widgetRepository = $this->getDoctrine()->getRepository('OroSidebarBundle:Widget');
        $items = $widgetRepository->getWidgets($this->getUser(), $placement);

        if (!$items) {
            $items = array();
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
     * @return Response
     */
    public function postAction()
    {
        $entity = new Widget();
        $entity->setWidgetName($this->getRequest()->get('widgetName'));

        // TODO: Remove this after changes to use widget_name
        $entity->setTitle($this->getRequest()->get('title'));
        $entity->setModule($this->getRequest()->get('module'));
        $entity->setIcon($this->getRequest()->get('icon'));
        // --- END ---

        $entity->setPosition($this->getRequest()->get('position'));
        $entity->setSettings($this->getRequest()->get('settings'));
        $entity->setPlacement($this->getRequest()->get('placement'));
        $entity->setUser($this->getUser());

        $manager = $this->getManager();
        $manager->persist($entity);
        $manager->flush();

        return $this->handleView(
            $this->view(array('id' => $entity->getId()), Codes::HTTP_CREATED)
        );
    }

    /**
     * REST PUT
     *
     * @param int $widgetId Widget instance id
     *
     * @ApiDoc(
     *  description="Update Sidebar Widget",
     *  resource=true
     * )
     * @return Response
     */
    public function putAction($widgetId)
    {
        /** @var \Oro\Bundle\SidebarBundle\Entity\Widget $entity */
        $entity = $this->getManager()->find('OroSidebarBundle:Widget', (int)$widgetId);
        if (!$entity) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }
        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view(null, Codes::HTTP_FORBIDDEN));
        }

        // TODO: Remove this after changes to use widget_name
        $entity->setTitle($this->getRequest()->get('title', $entity->getTitle()));
        $entity->setModule($this->getRequest()->get('module', $entity->getModule()));
        $entity->setIcon($this->getRequest()->get('icon', $entity->getIcon()));
        // --- END ---

        $entity->setPosition($this->getRequest()->get('position', $entity->getPosition()));
        $entity->setSettings($this->getRequest()->get('settings', $entity->getSettings()));
        $entity->setPlacement($this->getRequest()->get('placement', $entity->getPlacement()));

        $em = $this->getManager();
        $em->persist($entity);
        $em->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_OK));
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
        $entity = $this->getManager()->find('OroSidebarBundle:Widget', (int)$widgetId);
        if (!$entity) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }
        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view(null, Codes::HTTP_FORBIDDEN));
        }

        $em = $this->getManager();
        $em->remove($entity);
        $em->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
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
        return $this->getDoctrine()->getManagerForClass('OroSidebarBundle:Widget');
    }
}
