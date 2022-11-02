<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface;
use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Oro\Bundle\NavigationBundle\Provider\NavigationItemsProvider;
use Oro\Bundle\NavigationBundle\Utils\PinbarTabUrlNormalizer;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * REST API controller to manage navigation items.
 */
class NavigationItemController extends AbstractFOSRestController
{
    /**
     * @ApiDoc(
     *  description="Get all Navigation items for user",
     *  resource=true
     * )
     */
    public function getAction(string $type): Response
    {
        /** @var NavigationItemsProvider $navigationItemsProvider */
        $navigationItemsProvider = $this->container->get('oro_navigation.provider.navigation_items');
        $organization = $this->container->get('security.token_storage')->getToken()->getOrganization();

        $items = $navigationItemsProvider->getNavigationItems($this->getUser(), $organization, $type);

        return $this->handleView($this->view($items, Response::HTTP_OK));
    }

    /**
     * @ApiDoc(
     *  description="Add Navigation item",
     *  resource=true
     * )
     */
    public function postAction(Request $request, string $type): Response
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

        /** @var NavigationItemInterface $entity */
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

    private function validate(object $entity): array
    {
        $constraintViolationList = $this->get('validator')->validate($entity);
        /** @var ConstraintViolationInterface $constraintViolation */
        foreach ($constraintViolationList as $constraintViolation) {
            $errors[] = $constraintViolation->getMessage();
        }

        return $errors ?? [];
    }

    /**
     * @ApiDoc(
     *  description="Update Navigation item",
     *  resource=true
     * )
     */
    public function putIdAction(Request $request, string $type, $itemId): Response
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

        /** @var NavigationItemInterface $entity */
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
     * @ApiDoc(
     *  description="Remove Navigation item",
     *  resource=true
     * )
     */
    public function deleteIdAction(string $type, $itemId): Response
    {
        /** @var NavigationItemInterface $entity */
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
     */
    protected function validatePermissions(AbstractUser $user): bool
    {
        return
            is_a($user, $this->getUserClass(), true)
            && ($user->getId() === ($this->getUser() ? $this->getUser()->getId() : 0));
    }

    protected function getManager(): ObjectManager
    {
        return $this->getDoctrine()->getManagerForClass($this->getPinbarTabClass());
    }

    protected function getFactory(): ItemFactory
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

    protected function getPinbarTabClass(): string
    {
        return PinbarTab::class;
    }

    protected function getUserClass(): string
    {
        return User::class;
    }
}
