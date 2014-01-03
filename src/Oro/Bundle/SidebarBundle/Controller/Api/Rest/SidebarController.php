<?php

namespace Oro\Bundle\SidebarBundle\Controller\Api\Rest;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use Oro\Bundle\SidebarBundle\Entity\Repository\SidebarStateRepository;
use Oro\Bundle\SidebarBundle\Entity\SidebarState;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

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
        /** @var SidebarStateRepository $sidebarStateRepository */
        $sidebarStateRepository = $this->getDoctrine()->getRepository('OroSidebarBundle:SidebarState');
        $item = $sidebarStateRepository->getState($this->getUser(), $position);

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
     * @return Response
     */
    public function postAction()
    {
        $entity = new SidebarState();
        $entity->setPosition($this->getRequest()->get('position'));
        $entity->setState($this->getRequest()->get('state'));
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
     * @param int $stateId Sidebar state instance id
     *
     * @ApiDoc(
     *  description="Update Sidebar State",
     *  resource=true
     * )
     * @return Response
     */
    public function putAction($stateId)
    {
        /** @var \Oro\Bundle\SidebarBundle\Entity\SidebarState $entity */
        $entity = $this->getManager()->find('OroSidebarBundle:SidebarState', (int)$stateId);
        if (!$entity) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }
        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view(null, Codes::HTTP_FORBIDDEN));
        }
        $entity->setState($this->getRequest()->get('state', $entity->getState()));

        $em = $this->getManager();
        $em->persist($entity);
        $em->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_OK));
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
        return $this->getDoctrine()->getManagerForClass('OroSidebarBundle:SidebarState');
    }
}
