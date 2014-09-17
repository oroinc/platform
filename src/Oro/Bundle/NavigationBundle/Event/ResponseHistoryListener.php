<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use Oro\Bundle\EntityBundle\Event\OroEventManager;

class ResponseHistoryListener
{
    /**
     * @var null|\Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory
     */
    protected $navItemFactory = null;

    /**
     * @var \Symfony\Component\Security\Core\User\User|String
     */
    protected $user = null;

    /**
     * @var \Doctrine\ORM\EntityManager|null
     */
    protected $entityManager = null;

    /**
     * @var TitleServiceInterface
     */
    protected $titleService = null;

    /**
     * @var Organization
     */
    protected $organization;

    public function __construct(
        ItemFactory $navigationItemFactory,
        SecurityContextInterface $securityContext,
        EntityManager $entityManager,
        TitleServiceInterface $titleService
    ) {
        $this->navItemFactory = $navigationItemFactory;
        $this->user           = !$securityContext->getToken() || is_string($securityContext->getToken()->getUser())
            ? null : $securityContext->getToken()->getUser();

        $token = $securityContext->getToken() ? $securityContext->getToken() : null;
        if ($token instanceof OrganizationContextTokenInterface) {
            $this->organization = $securityContext->getToken()->getOrganizationContext();
        }

        $this->entityManager = $entityManager;
        $this->titleService  = $titleService;
    }

    /**
     * Process onResponse event, updates user history information
     *
     * @param  FilterResponseEvent $event
     *
     * @return bool|null
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            // Do not do anything
            return null;
        }

        $request  = $event->getRequest();
        $response = $event->getResponse();

        // check if a current request can be added to a history
        if (!$this->canAddToHistory($response, $request)) {
            return false;
        }

        $postArray = array(
            'url'          => $request->getRequestUri(),
            'user'         => $this->user,
            'organization' => $this->organization
        );

        /** @var $historyItem  NavigationHistoryItem */
        $historyItem = $this->entityManager
            ->getRepository('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem')
            ->findOneBy($postArray);

        if (!$historyItem) {
            $routeParameters = $request->get('_route_params');
            unset($routeParameters['id']);

            $entityId = filter_var($request->get('id'), FILTER_VALIDATE_INT);
            if (false !== $entityId) {
                $entityId = (int)$entityId;
            } else {
                $entityId = null;
            }

            $postArray['route']           = $request->get('_route');
            $postArray['routeParameters'] = $routeParameters;
            $postArray['entityId']        = $entityId;

            /** @var $historyItem \Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface */
            $historyItem = $this->navItemFactory->createItem(
                NavigationHistoryItem::NAVIGATION_HISTORY_ITEM_TYPE,
                $postArray
            );
        }

        $historyItem->setTitle($this->titleService->getSerialized());

        // force update
        $historyItem->doUpdate();

        // disable Doctrine events for history item processing
        $eventManager = $this->entityManager->getEventManager();
        if ($eventManager instanceof OroEventManager) {
            $eventManager->disableListeners('^Oro');
        }

        $this->entityManager->persist($historyItem);
        $this->entityManager->flush($historyItem);

        if ($eventManager instanceof OroEventManager) {
            $eventManager->clearDisabledListeners();
        }

        return true;
    }

    /**
     * Is request valid for adding to history
     *
     * @param  Response $response
     * @param  Request  $request
     *
     * @return bool
     */
    private function canAddToHistory(Response $response, Request $request)
    {
        $result =
            $response->getStatusCode() == 200
            && $request->getRequestFormat() == 'html'
            && $request->getMethod() == 'GET'
            && (!$request->isXmlHttpRequest()
                || $request->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER))
            && $this->user;

        if ($result) {
            $route  = $request->get('_route');
            $result = $route[0] != '_' && $route != 'oro_default';
        }

        if ($result && $response->headers->has('Content-Disposition')) {
            $contentDisposition = $response->headers->get('Content-Disposition');
            $result =
                (strpos($contentDisposition, ResponseHeaderBag::DISPOSITION_INLINE) !== 0)
                && (strpos($contentDisposition, ResponseHeaderBag::DISPOSITION_ATTACHMENT) !== 0);
        }

        return $result;
    }
}
