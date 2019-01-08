<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;

/**
 * Listener to fill organization column for audit grids using a separate single query
 */
class AuditGridOrganizationListener
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function addOrganizationSupport(OrmResultAfter $event)
    {
        foreach ($event->getRecords() as $record) {
            $organizationId = $record->getValue('organization');
            if (!$organizationId) {
                $record->setValue('organization', null);

                continue;
            }

            /** @var OrganizationRepository $organizationRepository */
            $organizationRepository = $this->doctrineHelper->getEntityRepository(Organization::class);
            $organization = $organizationRepository->find($organizationId);

            if (!$organization) {
                $record->setValue('organization', null);

                continue;
            }

            $record->setValue('organization', $organization->getName());
        }
    }
}
