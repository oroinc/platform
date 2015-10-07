<?php

namespace Oro\Bundle\UserBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;

class UserDeleteHandler extends DeleteHandler
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        $loggedUserId = $this->securityFacade->getLoggedUserId();
        if ($loggedUserId && $loggedUserId == $entity->getId()) {
            throw new ForbiddenException('self delete');
        }
        if ($this->securityFacade->hasUserSharedRecords($entity)) {
            throw new ForbiddenException('user has shared records');
        }
        parent::checkPermissions($entity, $em);
    }
}
