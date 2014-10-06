<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ImportStrategyListener
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param StrategyEvent $event
     */
    public function onProcessAfter(StrategyEvent $event)
    {
        $entity = $event->getEntity();

        if (!$this->isSupported($entity) || $entity->getOrganization()) {
            return;
        }

        $organization = $this->securityFacade->getOrganization();
        if ($organization) {
            $entity->setOrganization($organization);
        }
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isSupported($entity)
    {
        // TODO: use OrganizationAwareInterface after implementation of https://magecore.atlassian.net/browse/BAP-5542
        return method_exists($entity, 'getOrganization') && method_exists($entity, 'setOrganization');
    }
}
