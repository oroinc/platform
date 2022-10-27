<?php

namespace Oro\Bundle\SidebarBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SidebarBundle\Entity\AbstractSidebarState;
use Oro\Bundle\SidebarBundle\Entity\Repository\SidebarStateRepository;
use Oro\Bundle\SidebarBundle\Entity\SidebarState;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * REST API controller for the sidebar.
 */
class SidebarController extends AbstractFOSRestController
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
            $this->view($item, $item ? Response::HTTP_OK : Response::HTTP_NO_CONTENT)
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
            $this->view(['id' => $entity->getId()], Response::HTTP_CREATED)
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
            return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
        }
        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view(null, Response::HTTP_FORBIDDEN));
        }
        $entity->setState($request->get('state', $entity->getState()));

        $em = $this->getManager();
        $em->persist($entity);
        $em->flush();

        return $this->handleView($this->view([], Response::HTTP_OK));
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
     * @return \Doctrine\Persistence\ObjectManager
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
        return SidebarState::class;
    }
}
