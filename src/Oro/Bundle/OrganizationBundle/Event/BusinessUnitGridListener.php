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

        $where = $config->offsetGetByPath('[source][query][where][and]', []);

        if ($accessLevel == AccessLevel::GLOBAL_LEVEL) {
            $leftJoins = $config->offsetGetByPath('[source][query][join][inner]', []);
            $leftJoins[] = ['join' => 'u.organization', 'alias' => 'org'];
            $config->offsetSetByPath('[source][query][join][inner]', $leftJoins);

            $where = array_merge(
                $where,
                ['org.id in (' . $organization->getId() . ')']
            );
        } elseif ($accessLevel !== AccessLevel::SYSTEM_LEVEL) {
            $resultBuIds = [];
            if ($accessLevel == AccessLevel::LOCAL_LEVEL) {
                $resultBuIds = $this->treeProvider->getTree()->getUserBusinessUnitIds(
                    $user->getId(),
                    $organization->getId()
                );
            } elseif ($accessLevel == AccessLevel::DEEP_LEVEL) {
                $resultBuIds = $this->treeProvider->getTree()->getUserSubordinateBusinessUnitIds(
                    $user->getId(),
                    $organization->getId()
                );
            }
            if (count($resultBuIds)) {
                $where = array_merge(
                    $where,
                    ['u.id in (' . implode(', ', $resultBuIds) . ')']
                );
            } else {
                // There are no records to show, make query to return empty result
                $where = array_merge($where, ['1 = 0']);
            }
        }
        if (count($where)) {
            $config->offsetSetByPath('[source][query][where][and]', $where);
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
