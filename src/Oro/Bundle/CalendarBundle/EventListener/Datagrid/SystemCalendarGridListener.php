<?php

namespace Oro\Bundle\CalendarBundle\EventListener\Datagrid;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class SystemCalendarGridListener
{
    const GRID_WHERE_PATH = '[source][query][where][and]';

    /** @var ServiceLink */
    protected $securityContextLink;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var AclVoter */
    protected $aclVoter;

    public function __construct(
        ServiceLink $securityContextLink,
        SecurityFacade $securityFacade,
        AclVoter $aclVoter = null
    ) {
        $this->securityContextLink = $securityContextLink;
        $this->securityFacade      = $securityFacade;
        $this->aclVoter            = $aclVoter;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config       = $event->getConfig();
        $organization = $this->getSecurityContext()->getToken()->getOrganizationContext();
        $observer     = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        $accessLevel  = $observer->getAccessLevel();

        $where = $config->offsetGetByPath(self::GRID_WHERE_PATH, []);

        if ($accessLevel !== AccessLevel::SYSTEM_LEVEL) {
            $where = array_merge($where, ['o.id in (' . $organization->getId() . ')']);
        }

        if (!$this->securityFacade->isGranted('oro_system_calendar_view')) {
            $where = array_merge($where, ['sc.public = 1']);
        }

        if (count($where)) {
            $config->offsetSetByPath(self::GRID_WHERE_PATH, $where);
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
