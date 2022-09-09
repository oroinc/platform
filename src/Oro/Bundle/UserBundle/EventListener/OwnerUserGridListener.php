<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Applies ACL to the datagrid for select owner users.
 * This datagrid does not use search index or an ACL helper to limit data.
 */
class OwnerUserGridListener
{
    private ManagerRegistry $doctrine;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenAccessorInterface $tokenAccessor;
    private AclVoterInterface $aclVoter;
    private OwnerTreeProvider $treeProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        OwnerTreeProvider $treeProvider,
        AclVoterInterface $aclVoter
    ) {
        $this->doctrine = $doctrine;
        $this->aclVoter = $aclVoter;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->treeProvider = $treeProvider;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $parameters = $event->getDatagrid()->getParameters();
        $entity = $parameters->get('entity');
        $entityClass = $entity
            ? str_replace('_', '\\', $entity)
            : User::class;
        $entityId = $parameters->get('entity_id');
        $object = $entityId
            ? $this->doctrine->getRepository($entityClass)->find((int)$entityId)
            : ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $entityClass);

        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        $this->authorizationChecker->isGranted($parameters->get('permission'), $object);
        $this->applyAcl(
            $event->getConfig(),
            $observer->getAccessLevel(),
            $this->tokenAccessor->getUser(),
            $this->tokenAccessor->getOrganization()
        );
    }

    protected function applyAcl(
        DatagridConfiguration $config,
        int $accessLevel,
        User $user,
        Organization $organization
    ): void {
        $query = $config->getOrmQuery();
        if (AccessLevel::BASIC_LEVEL === $accessLevel) {
            $query->addAndWhere('u.id = ' . $user->getId());
        } elseif (AccessLevel::GLOBAL_LEVEL === $accessLevel) {
            $query->addInnerJoin('u.organizations', 'org');
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
            $query->addInnerJoin('u.businessUnits', 'bu');
            if ($resultBuIds) {
                $query->addAndWhere('bu.id in (' . implode(', ', $resultBuIds) . ')');
            } else {
                // There are no records to show, make query to return empty result
                $query->addAndWhere('1 = 0');
            }
        }
    }
}
