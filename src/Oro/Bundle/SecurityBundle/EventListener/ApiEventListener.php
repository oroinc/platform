<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\SoapBundle\Event\FindAfter;
use Oro\Bundle\SoapBundle\Event\GetListBefore;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ApiEventListener
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param Request        $request
     * @param SecurityFacade $securityFacade
     * @param AclHelper      $aclHelper
     */
    public function __construct(Request $request, SecurityFacade $securityFacade, AclHelper $aclHelper)
    {
        $this->request = $request;
        $this->securityFacade = $securityFacade;
        $this->aclHelper = $aclHelper;
    }

    /**
     * Add ACL check to API get list query criteria
     *
     * @param GetListBefore $event
     */
    public function onGetListBefore(GetListBefore $event)
    {
        $acl = $this->securityFacade->getRequestAcl($this->request, true);
        if ($acl && $event->getClassName() === $acl->getClass()) {
            $event->setCriteria(
                $this->aclHelper->applyAclToCriteria(
                    $event->getClassName(),
                    $event->getCriteria(),
                    $acl->getPermission()
                )
            );
        }
    }

    /**
     * Check access to current object after entity was selected
     *
     * @param FindAfter $event
     */
    public function onFindAfter(FindAfter $event)
    {
        $this->checkObjectAccess($event->getEntity());
    }

    /**
     * @param $object
     * @throws AccessDeniedException
     */
    protected function checkObjectAccess($object)
    {
        if (is_object($object) && $this->securityFacade->isRequestObjectIsGranted($this->request, $object) === -1) {
            throw new AccessDeniedException();
        }
    }
}
