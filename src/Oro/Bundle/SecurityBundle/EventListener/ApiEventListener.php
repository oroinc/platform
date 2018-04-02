<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SoapBundle\Event\FindAfter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ApiEventListener
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var RequestAuthorizationChecker */
    protected $requestAuthorizationChecker;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param RequestAuthorizationChecker $requestAuthorizationChecker
     * @param AclHelper                   $aclHelper
     * @param RequestStack                $requestStack
     */
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
     *
     * @param FindAfter $event
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
