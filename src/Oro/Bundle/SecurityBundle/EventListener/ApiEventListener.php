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
     * @param SecurityFacade $securityFacade
     * @param AclHelper      $aclHelper
     */
    public function __construct(SecurityFacade $securityFacade, AclHelper $aclHelper)
    {
        $this->securityFacade = $securityFacade;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Check access to current object after entity was selected
     *
     * @param FindAfter $event
     */
    public function onFindAfter(FindAfter $event)
    {
        if (!$this->request) {
            return;
        }

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
