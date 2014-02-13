<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;

class ResponseHistoryListener
{
    /**
     * @var null|\Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory
     */
    protected $navItemFactory = null;

    /**
     * @var \Symfony\Component\Security\Core\User\User|String
     */
    protected $user  = null;

    /**
     * @var \Doctrine\ORM\EntityManager|null
     */
    protected $em = null;

    /**
     * @var TitleServiceInterface
     */
    protected $titleService = null;

    public function __construct(
        ItemFactory $navigationItemFactory,
        SecurityContextInterface $securityContext,
        EntityManager $entityManager,
        TitleServiceInterface $titleService
    ) {
        $this->navItemFactory = $navigationItemFactory;
        $this->user = !$securityContext->getToken() ||  is_string($securityContext->getToken()->getUser())
                      ? null : $securityContext->getToken()->getUser();
        $this->em = $entityManager;
        $this->titleService = $titleService;
    }

    /**
     * Process onReslonse event, updates user history information
     *
     * @param  FilterResponseEvent $event
     * @return bool|void
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            // Do not do anything
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // check if a current request can be added to a history
        if (!$this->canAddToHistory($response, $request)) {
            return false;
        }

        $postArray = array(
            'url'      => $request->getRequestUri(),
            'user'     => $this->user,
        );

        /** @var $historyItem  NavigationHistoryItem */
        $historyItem = $this->em->getRepository('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem')
                                ->findOneBy($postArray);
        if (!$historyItem) {
            /** @var $historyItem \Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface */
            $historyItem = $this->navItemFactory->createItem(
                NavigationHistoryItem::NAVIGATION_HISTORY_ITEM_TYPE,
                $postArray
            );
        }

        $historyItem->setTitle($this->titleService->getSerialized());

        // force update
        $historyItem->doUpdate();

        $this->em->persist($historyItem);
        $this->em->flush($historyItem);

        return true;
    }

    /**
     * Is request valid for adding to history
     *
     * @param  Response $response
     * @param  Request  $request
     * @return bool
     */
    private function canAddToHistory(Response $response, Request $request)
    {
        $result = !(
            $response->getStatusCode() != 200
            || $request->getRequestFormat() != 'html'
            || $request->getMethod() != 'GET'
            || ($request->isXmlHttpRequest()
                && !$request->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER))
            || is_null($this->user)
        );

        if ($result) {
            $route = $request->get('_route');
            $result = !(
                $route[0] == '_'
                || $route == 'oro_default'
            );
        }

        if ($result && $response->headers->has('Content-Disposition')) {
            $contentDisposition = $response->headers->get('Content-Disposition');
            $result = !(
                strpos($contentDisposition, ResponseHeaderBag::DISPOSITION_INLINE) === 0
                || strpos($contentDisposition, ResponseHeaderBag::DISPOSITION_ATTACHMENT) === 0
            );
        }

        return $result;
    }
}
