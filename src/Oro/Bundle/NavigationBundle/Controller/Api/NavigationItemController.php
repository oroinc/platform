<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Util\ClassUtils;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\Repository\NavigationRepositoryInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("navigationitems")
 * @NamePrefix("oro_api_")
 */
class NavigationItemController extends FOSRestController
{
    /**
     * REST GET list
     *
     * @param string $type
     *
     * @ApiDoc(
     *  description="Get all Navigation items for user",
     *  resource=true
     * )
     * @return Response
     */
    public function getAction($type)
    {
        /** @var $entity \Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface */
        $entity = $this->getFactory()->createItem($type, array());

        /** @var $repo NavigationRepositoryInterface */
        $repo = $this->getDoctrine()->getRepository(ClassUtils::getClass($entity));
        $organization = $this->container->get('security.token_storage')->getToken()->getOrganizationContext();
        $items = $repo->getNavigationItems($this->getUser(), $organization, $type);

        return $this->handleView(
            $this->view($items, is_array($items) ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND)
        );
    }

    /**
     * REST POST
     *
     * @param Request $request
     * @param string $type
     *
     * @ApiDoc(
     *  description="Add Navigation item",
     *  resource=true
     * )
     * @return Response
     */
    public function postAction(Request $request, $type)
    {
        $params = $request->request->all();

        if (empty($params) || empty($params['type'])) {
            return $this->handleView(
                $this->view(
                    array('message' => 'Wrong JSON inside POST body'),
                    Codes::HTTP_BAD_REQUEST
                )
            );
        }

        $params['user'] = $this->getUser();
        $params['url']  = $this->getStateUrl($params['url']);
        $params['organization'] = $this->container->get('security.token_storage')->getToken()->getOrganizationContext();

        /** @var $entity \Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface */
        $entity = $this->getFactory()->createItem($type, $params);

        if (!$entity) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        $em = $this->getManager();

        $em->persist($entity);
        $em->flush();

        return $this->handleView(
            $this->view(array('id' => $entity->getId(), 'url' => $params['url']), Codes::HTTP_CREATED)
        );
    }

    /**
     * REST PUT
     *
     * @param Request $request
     * @param string $type
     * @param int    $itemId Navigation item id
     *
     * @ApiDoc(
     *  description="Update Navigation item",
     *  resource=true
     * )
     * @return Response
     */
    public function putIdAction(Request $request, $type, $itemId)
    {
        $params = $request->request->all();

        if (empty($params)) {
            return $this->handleView(
                $this->view(
                    array('message' => 'Wrong JSON inside POST body'),
                    Codes::HTTP_BAD_REQUEST
                )
            );
        }

        /** @var $entity \Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface */
        $entity = $this->getFactory()->findItem($type, (int) $itemId);

        if (!$entity) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view(array(), Codes::HTTP_FORBIDDEN));
        }

        if (isset($params['url']) && !empty($params['url'])) {
            $params['url'] = $this->getStateUrl($params['url']);
        }

        $entity->setValues($params);

        $em = $this->getManager();

        $em->persist($entity);
        $em->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_OK));
    }

    /**
     * REST DELETE
     *
     * @param string $type
     * @param int    $itemId
     *
     * @ApiDoc(
     *  description="Remove Navigation item",
     *  resource=true
     * )
     * @return Response
     */
    public function deleteIdAction($type, $itemId)
    {
        /** @var $entity \Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface */
        $entity = $this->getFactory()->findItem($type, (int) $itemId);
        if (!$entity) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }
        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view(array(), Codes::HTTP_FORBIDDEN));
        }

        $em = $this->getManager();
        $em->remove($entity);
        $em->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
    }

    /**
     * Validate permissions on pinbar
     *
     * @param  AbstractUser $user
     * @return bool
     */
    protected function validatePermissions(AbstractUser $user)
    {
        return is_a($user, $this->getUserClass(), true) &&
            ($user->getId() === ($this->getUser() ? $this->getUser()->getId() : 0));
    }

    /**
     * Get entity Manager
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getManager()
    {
        return $this->getDoctrine()->getManagerForClass($this->getPinbarTabClass());
    }

    /**
     * Get entity factory
     *
     * @return ItemFactory
     */
    protected function getFactory()
    {
        return $this->get('oro_navigation.item.factory');
    }

    /**
     * Check if navigation item has corresponding page state and return modified URL
     *
     * @param  string $url Original URL
     * @return string Modified URL
     */
    protected function getStateUrl($url)
    {
        $state = $this->getPageStateRepository()->findOneByPageId(base64_encode($url));

        return is_null($state)
            ? $url
            : $url . (strpos($url, '?') ? '&restore=1' : '?restore=1');
    }

    /**
     * @return ObjectRepository
     */
    protected function getPageStateRepository()
    {
        return $this->getDoctrine()->getRepository('OroNavigationBundle:PageState');
    }

    /**
     * @return string
     */
    protected function getPinbarTabClass()
    {
        return $this->getParameter('oro_navigation.entity.pinbar_tab.class');
    }

    /**
     * @return string
     */
    protected function getUserClass()
    {
        return $this->getParameter('oro_user.entity.class');
    }
}
