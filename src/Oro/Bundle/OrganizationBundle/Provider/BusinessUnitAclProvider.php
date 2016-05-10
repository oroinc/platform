<?php

namespace Oro\Bundle\OrganizationBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\UserInterface;

class BusinessUnitAclProvider
{
    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var bool */
    protected $isAssignGranted;

    /** @var int */
    protected $accessLevel;

    /** @var UserInterface */
    protected $currentUser;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var OwnerTreeProvider */
    protected $treeProvider;

    /** @var OneShotIsGrantedObserver */
    protected $observer;

    /**
     * @param BusinessUnitManager $businessUnitManager
     * @param SecurityFacade      $securityFacade
     * @param AclVoter            $aclVoter
     * @param OwnerTreeProvider   $treeProvider
     */
    public function __construct(
        BusinessUnitManager $businessUnitManager,
        SecurityFacade $securityFacade,
        AclVoter $aclVoter,
        OwnerTreeProvider $treeProvider
    ) {
        $this->businessUnitManager       = $businessUnitManager;
        $this->securityFacade            = $securityFacade;
        $this->aclVoter                  = $aclVoter;
        $this->treeProvider              = $treeProvider;
    }

    /**
     * @param string $dataClassName
     * @param string $permission
     * @return array
     */
    public function getBusinessUnitIds($dataClassName, $permission = 'VIEW')
    {
        $this->getCurrentUser();
        $this->checkIsGranted($permission, 'entity:' . $dataClassName);

        if ($this->isAssignGranted) {
            return $this->getIds();
        }

        return [null];
    }

    /**
     * @param OneShotIsGrantedObserver $observer
     * @return $this
     */
    public function addOneShotIsGrantedObserver(OneShotIsGrantedObserver $observer)
    {
        $this->observer = $observer;
        return $this;
    }

    /**
     * Check is granting user to object in given permission
     *
     * @param string        $permission
     * @param object|string $object
     */
    protected function checkIsGranted($permission, $object)
    {
        if ($this->observer) {
            $this->aclVoter->addOneShotIsGrantedObserver($this->observer);
            $this->isAssignGranted = $this->securityFacade->isGranted($permission, $object);
            $this->accessLevel = $this->observer->getAccessLevel();
        }
    }

    /**
     * Get business units ids for current user for current access level
     *
     * @return array
     */
    protected function getIds()
    {
        if (AccessLevel::SYSTEM_LEVEL === $this->accessLevel) {
            return $this->businessUnitManager->getBusinessUnitIds();
        } elseif (AccessLevel::LOCAL_LEVEL === $this->accessLevel) {
            return $this->treeProvider->getTree()->getUserBusinessUnitIds(
                $this->currentUser->getId(),
                $this->getOrganizationContextId()
            );
        } elseif (AccessLevel::DEEP_LEVEL === $this->accessLevel) {
            return $this->treeProvider->getTree()->getUserSubordinateBusinessUnitIds(
                $this->currentUser->getId(),
                $this->getOrganizationContextId()
            );
        } elseif (AccessLevel::GLOBAL_LEVEL === $this->accessLevel) {
            return $this->businessUnitManager->getBusinessUnitIds($this->getOrganizationContextId());
        }

        return [null];
    }

    /**
     * @return null|UserInterface
     */
    protected function getCurrentUser()
    {
        if (null === $this->currentUser) {
            $user = $this->securityFacade->getLoggedUser();
            if ($user instanceof UserInterface) {
                $this->currentUser = $user;
            }
        }

        return $this->currentUser;
    }

    /**
     * @return int
     */
    protected function getOrganizationContextId()
    {
        return $this->securityFacade->getOrganization()->getId();
    }
}
