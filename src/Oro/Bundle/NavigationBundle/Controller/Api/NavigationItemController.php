<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface;
use Oro\Bundle\NavigationBundle\Entity\PageState;
use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Oro\Bundle\NavigationBundle\Provider\NavigationItemsProvider;
use Oro\Bundle\NavigationBundle\Utils\PinbarTabUrlNormalizer;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Provides API actions for managing navigation items.
 *
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
        /** @var NavigationItemsProvider $navigationItemsProvider */
        $navigationItemsProvider = $this->container->get('oro_navigation.provider.navigation_items');
        $organization = $this->container->get('security.token_storage')->getToken()->getOrganization();

        $items = $navigationItemsProvider->getNavigationItems($this->getUser(), $organization, $type);

        return $this->handleView(
            $this->view($items, \is_array($items) ? Response::HTTP_OK : Response::HTTP_NOT_FOUND)
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
                    ['message' => 'Wrong JSON inside POST body'],
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        $params['user'] = $this->getUser();
        $params['url'] = $this->normalizeUrl($params['url'], $params['type']);
        $params['organization'] = $this->container->get('security.token_storage')->getToken()->getOrganization();

        /** @var $entity NavigationItemInterface */
        $entity = $this->getFactory()->createItem($type, $params);

        if (!$entity) {
            return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
        }

        $errors = $this->validate($entity);
        if ($errors) {
            return $this->handleView(
                $this->view(['message' => implode(PHP_EOL, $errors)], Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        }

        $em = $this->getManager();

        $em->persist($entity);
        $em->flush();

        return $this->handleView(
            $this->view(['id' => $entity->getId(), 'url' => $params['url']], Response::HTTP_CREATED)
        );
    }

    /**
     * @param mixed $entity
     *
     * @return array
     */
    private function validate($entity): array
    {
        $constraintViolationList = $this->get('validator')->validate($entity);
        /** @var ConstraintViolationInterface $constraintViolation */
        foreach ($constraintViolationList as $constraintViolation) {
            $errors[] = $constraintViolation->getMessage();
        }

        return $errors ?? [];
    }

    /**
     * REST PUT
     *
     * @param Request $request
     * @param string $type
     * @param int $itemId Navigation item id
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
                    ['message' => 'Wrong JSON inside POST body'],
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        /** @var $entity NavigationItemInterface */
        $entity = $this->getFactory()->findItem($type, (int)$itemId);

        if (!$entity) {
            return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
        }

        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view([], Response::HTTP_FORBIDDEN));
        }

        if (isset($params['url']) && !empty($params['url'])) {
            $params['url'] = $this->normalizeUrl($params['url'], $type);
        }

        $entity->setValues($params);

        $em = $this->getManager();

        $em->persist($entity);
        $em->flush();

        return $this->handleView($this->view([], Response::HTTP_OK));
    }

    /**
     * REST DELETE
     *
     * @param string $type
     * @param int $itemId
     *
     * @ApiDoc(
     *  description="Remove Navigation item",
     *  resource=true
     * )
     * @return Response
     */
    public function deleteIdAction($type, $itemId)
    {
        /** @var $entity NavigationItemInterface */
        $entity = $this->getFactory()->findItem($type, (int)$itemId);
        if (!$entity) {
            return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
        }
        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view([], Response::HTTP_FORBIDDEN));
        }

        $em = $this->getManager();
        $em->remove($entity);
        $em->flush();

        return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
    }

    /**
     * Validate permissions on pinbar
     *
     * @param AbstractUser $user
     *
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
     * @return ObjectManager
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
     * Normalizes URL.
     *
     * @param string $url Original URL
     * @param string $type Navigation item type
     *
     * @return string Normalized URL
     */
    private function normalizeUrl(string $url, string $type): string
    {
        /** @var PinbarTabUrlNormalizer $normalizer */
        $normalizer = $this->container->get('oro_navigation.utils.pinbar_tab_url_normalizer');

        // Adds "restore" GET parameter to URL if we are dealing with pinbar. Page state for pinned page is restored
        // only if this parameter is specified.
        if ($type === 'pinbar') {
            $urlInfo = parse_url($url);
            parse_str($urlInfo['query'] ?? '', $query);

            if (!isset($query['restore'])) {
                $query['restore'] = 1;
                $url = sprintf('%s?%s', $urlInfo['path'] ?? '', http_build_query($query));
            }
        }

        return $normalizer->getNormalizedUrl($url);
    }

    /**
     * @return ObjectRepository
     */
    protected function getPageStateRepository()
    {
        return $this->getDoctrine()->getRepository(PageState::class);
    }

    /**
     * @return string
     */
    protected function getPinbarTabClass()
    {
        return PinbarTab::class;
    }

    /**
     * @return string
     */
    protected function getUserClass()
    {
        return User::class;
    }
}
