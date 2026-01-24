<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SoapBundle\Event\FindAfter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Handles API events to enforce ACL access control.
 *
 * This listener intercepts API find operations and checks whether the current user
 * has access to the returned entity. If access is denied, it throws an {@see AccessDeniedException}
 * to prevent unauthorized data exposure through the API.
 */
class ApiEventListener
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var RequestAuthorizationChecker */
    protected $requestAuthorizationChecker;

    /** @var AclHelper */
    protected $aclHelper;

    public function __construct(
        RequestAuthorizationChecker $requestAuthorizationChecker,
        AclHelper $aclHelper,
        RequestStack $requestStack
    ) {
        $this->requestAuthorizationChecker = $requestAuthorizationChecker;
        $this->aclHelper = $aclHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * Check access to current object after entity was selected
     */
    public function onFindAfter(FindAfter $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $this->checkObjectAccess($event->getEntity(), $request);
    }

    /**
     * @param mixed $object
     * @param Request $request
     * @throws AccessDeniedException
     */
    protected function checkObjectAccess($object, Request $request)
    {
        if (is_object($object)
            && $this->requestAuthorizationChecker->isRequestObjectIsGranted($request, $object) === -1
        ) {
            throw new AccessDeniedException();
        }
    }
}
