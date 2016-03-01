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

use Oro\Bundle\SidebarBundle\Entity\AbstractSidebarState;
use Oro\Bundle\SidebarBundle\Entity\Repository\SidebarStateRepository;

/**
 * @RouteResource("sidebars")
 * @NamePrefix("oro_api_")
 */
class SidebarController extends FOSRestController
{
    /**
     * REST GET
     *
     * @ApiDoc(
     *      description="Get sidebar state by position",
     *      resource=true
     * )
     * @param string $position
     * @return Response
     */
    public function getAction($position)
    {
        $item = $this->getRepository()->getState($this->getUser(), $position);

        return $this->handleView(
            $this->view($item, Codes::HTTP_OK)
        );
    }

    /**
     * REST POST
     *
     * @ApiDoc(
     *  description="Add Sidebar State",
     *  resource=true
     * )
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request)
    {
        $stateClass = $this->getSidebarStateClass();
        /** @var AbstractSidebarState $entity */
        $entity = new $stateClass();
        $entity->setPosition($request->get('position'));
        $entity->setState($request->get('state'));
        $entity->setUser($this->getUser());

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
     * @param int $stateId Sidebar state instance id
     *
     * @param Request $request
     * @return Response
     * @ApiDoc(
     *  description="Update Sidebar State",
     *  resource=true
     * )
     */
    public function putAction($stateId, Request $request)
    {
        $entity = $this->getManager()->find($this->getSidebarStateClass(), (int)$stateId);
        if (!$entity) {
            return $this->handleView($this->view([], Codes::HTTP_NOT_FOUND));
        }
        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view(null, Codes::HTTP_FORBIDDEN));
        }
        $entity->setState($request->get('state', $entity->getState()));

        $em = $this->getManager();
        $em->persist($entity);
        $em->flush();

        return $this->handleView($this->view([], Codes::HTTP_OK));
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
        return $this->getDoctrine()->getManagerForClass($this->getSidebarStateClass());
    }

    /**
     * @return SidebarStateRepository
     */
    protected function getRepository()
    {
        return $this->getManager()->getRepository($this->getSidebarStateClass());
    }

    /**
     * @return string
     */
    protected function getSidebarStateClass()
    {
        return $this->getParameter('oro_sidebar.entity.sidebar_state.class');
    }
}
