<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class BusinessUnitGridListener
{
    /** @var ServiceLink */
    protected $securityContextLink;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var OwnerTreeProvider */
    protected $treeProvider;

    public function __construct(
        ServiceLink $securityContextLink,
        OwnerTreeProvider $treeProvider,
        AclVoter $aclVoter = null
    ) {
        $this->aclVoter = $aclVoter;
        $this->securityContextLink = $securityContextLink;
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
        $this->getSecurityContext()->isGranted('VIEW', $object);

        $user = $this->getSecurityContext()->getToken()->getUser();
        $organization = $this->getSecurityContext()->getToken()->getOrganizationContext();
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

    /**
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->securityContextLink->getService();
    }
}
