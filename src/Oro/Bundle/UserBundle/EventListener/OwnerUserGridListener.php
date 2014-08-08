<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

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
        $config = $event->getConfig();
        $parameters = $event->getDatagrid()->getParameters();
        $permission = $parameters->get('permission');
        $entityClass = str_replace('_', '\\', $parameters->get('entity'));
        $entityId = $parameters->get('entity_id');

        if ($entityId) {
            $object = $this->em->getRepository($entityClass)->find((int)$entityId);
        } else {
            $object = 'entity:' . $entityClass;
        }

        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        $this->getSecurityContext()->isGranted($permission, $object);
        $user = $this->getSecurityContext()->getToken()->getUser();
        $organization = $this->getSecurityContext()->getToken()->getOrganizationContext();
        $accessLevel = $observer->getAccessLevel();

        $where = $config->offsetGetByPath('[source][query][where][and]', []);
        /** todo: refactor this check usages */
        if ($accessLevel == AccessLevel::BASIC_LEVEL) {
            $where = array_merge(
                $where,
                ['u.id = ' . $user->getId()]
            );
        } elseif ($accessLevel == AccessLevel::GLOBAL_LEVEL) {
            $leftJoins = $config->offsetGetByPath('[source][query][join][inner]', []);
            $leftJoins[] = ['join' => 'u.organizations', 'alias' => 'org'];
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

            $leftJoins = $config->offsetGetByPath('[source][query][join][inner]', []);
            $leftJoins[] = ['join' => 'u.businessUnits', 'alias' => 'bu'];
            $config->offsetSetByPath('[source][query][join][inner]', $leftJoins);

            $where = array_merge(
                $where,
                ['bu.id in (' . implode(', ', $resultBuIds) . ')']
            );
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
