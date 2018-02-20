<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BusinessUnitGridListener
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var OwnerTreeProvider */
    protected $treeProvider;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param OwnerTreeProvider             $treeProvider
     * @param AclVoter|null                 $aclVoter
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        OwnerTreeProvider $treeProvider,
        AclVoter $aclVoter = null
    ) {
        $this->aclVoter = $aclVoter;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->treeProvider = $treeProvider;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $object = 'entity:Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';

        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        $this->authorizationChecker->isGranted('VIEW', $object);

        $user = $this->tokenAccessor->getUser();
        $organization = $this->tokenAccessor->getOrganization();
        $accessLevel = $observer->getAccessLevel();

        $query = $config->getOrmQuery();

        if (AccessLevel::GLOBAL_LEVEL === $accessLevel) {
            $query->addInnerJoin('u.organization', 'org');
            $query->addAndWhere('org.id in (' . $organization->getId() . ')');
        } elseif (AccessLevel::SYSTEM_LEVEL !== $accessLevel) {
            $resultBuIds = [];
            if (AccessLevel::LOCAL_LEVEL === $accessLevel) {
                $resultBuIds = $this->treeProvider->getTree()->getUserBusinessUnitIds(
                    $user->getId(),
                    $organization->getId()
                );
            } elseif (AccessLevel::DEEP_LEVEL === $accessLevel) {
                $resultBuIds = $this->treeProvider->getTree()->getUserSubordinateBusinessUnitIds(
                    $user->getId(),
                    $organization->getId()
                );
            }
            if (count($resultBuIds)) {
                $query->addAndWhere('u.id in (' . implode(', ', $resultBuIds) . ')');
            } else {
                // There are no records to show, make query to return empty result
                $query->addAndWhere('1 = 0');
            }
        }
    }
}
