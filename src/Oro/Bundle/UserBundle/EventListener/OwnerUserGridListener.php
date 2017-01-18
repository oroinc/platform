<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

/**
 * Owner users select grid. This grid does not use search index or an ACL helper to limit data.
 *
 * Class OwnerUserGridListener
 * @package Oro\Bundle\UserBundle\EventListener
 */
class OwnerUserGridListener
{
    /** @var EntityManager */
    protected $em;

    /** @var ServiceLink */
    protected $securityContextLink;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var OwnerTreeProvider */
    protected $treeProvider;

    /**
     * @param EntityManager     $em
     * @param ServiceLink       $securityContextLink
     * @param OwnerTreeProvider $treeProvider
     * @param AclVoter          $aclVoter
     */
    public function __construct(
        EntityManager $em,
        ServiceLink $securityContextLink,
        OwnerTreeProvider $treeProvider,
        AclVoter $aclVoter = null
    ) {
        $this->em                  = $em;
        $this->aclVoter            = $aclVoter;
        $this->securityContextLink = $securityContextLink;
        $this->treeProvider        = $treeProvider;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $parameters  = $event->getDatagrid()->getParameters();
        $permission  = $parameters->get('permission');
        if ($parameters->get('entity')) {
            $entityClass = str_replace('_', '\\', $parameters->get('entity'));
        } else {
            $entityClass = 'Oro\Bundle\UserBundle\Entity\User';
        }

        $entityId    = $parameters->get('entity_id');

        if ($entityId) {
            $object = $this->em->getRepository($entityClass)->find((int)$entityId);
        } else {
            $object = 'entity:' . $entityClass;
        }

        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        $this->getSecurityContext()->isGranted($permission, $object);
        $accessLevel = $observer->getAccessLevel();

        $config       = $event->getConfig();
        $user         = $this->getSecurityContext()->getToken()->getUser();
        $organization = $this->getSecurityContext()->getToken()->getOrganizationContext();

        $this->applyACL($config, $accessLevel, $user, $organization);
    }

    /**
     * Add user limitation
     *
     * @param DatagridConfiguration $config
     * @param string                $accessLevel
     * @param User                  $user
     * @param Organization          $organization
     *
     * @throws \Exception
     */
    protected function applyACL(DatagridConfiguration $config, $accessLevel, User $user, Organization $organization)
    {
        $query = $config->getOrmQuery();
        /** todo: refactor this check usages */
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

    /**
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->securityContextLink->getService();
    }
}
