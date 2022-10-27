<?php

namespace Oro\Bundle\OrganizationBundle\Provider;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides IDs of business units from which the current user is granted to access to a specific entity type.
 */
class BusinessUnitAclProvider
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var AclVoterInterface */
    protected $aclVoter;

    /** @var OwnerTreeProvider */
    protected $treeProvider;

    /** @var OneShotIsGrantedObserver */
    protected $observer;

    /** @var string */
    protected $accessLevel;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        AclVoterInterface $aclVoter,
        OwnerTreeProvider $treeProvider
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->aclVoter = $aclVoter;
        $this->treeProvider = $treeProvider;
        $this->observer = new OneShotIsGrantedObserver();
    }

    /**
     * Get business units ids for current user and current entity access level
     *
     * @param string $dataClassName
     * @param string $permission
     * @return array
     */
    public function getBusinessUnitIds($dataClassName, $permission = 'VIEW')
    {
        $ids = [];

        $this->accessLevel = $this->getAccessLevel($permission, 'entity:' . $dataClassName);
        $currentUser = $this->tokenAccessor->getUser();

        if (!$currentUser || !$this->accessLevel) {
            return $ids;
        }

        if (AccessLevel::SYSTEM_LEVEL === $this->accessLevel) {
            $ids = $this->treeProvider->getTree()->getAllBusinessUnitIds();
        } elseif (AccessLevel::GLOBAL_LEVEL === $this->accessLevel) {
            $ids = $this->treeProvider->getTree()->getOrganizationBusinessUnitIds(
                $this->getOrganizationId()
            );
        } elseif (AccessLevel::DEEP_LEVEL === $this->accessLevel) {
            $ids = $this->treeProvider->getTree()->getUserSubordinateBusinessUnitIds(
                $currentUser->getId(),
                $this->getOrganizationId()
            );
        } elseif (AccessLevel::LOCAL_LEVEL === $this->accessLevel) {
            $ids = $this->treeProvider->getTree()->getUserBusinessUnitIds(
                $currentUser->getId(),
                $this->getOrganizationId()
            );
        }

        return $ids;
    }

    /**
     * @return string
     */
    public function getProcessedEntityAccessLevel()
    {
        return $this->accessLevel;
    }

    /**
     * Get object's access level
     *
     * @param string $permission
     * @param string $object
     * @return null|int
     */
    protected function getAccessLevel($permission, $object)
    {
        $this->aclVoter->addOneShotIsGrantedObserver($this->observer);
        if ($this->authorizationChecker->isGranted($permission, $object)) {
            return $this->observer->getAccessLevel();
        }

        return null;
    }

    /**
     * @return int
     */
    protected function getOrganizationId()
    {
        return $this->tokenAccessor->getOrganization()->getId();
    }
}
