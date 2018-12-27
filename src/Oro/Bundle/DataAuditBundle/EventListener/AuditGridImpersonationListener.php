<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\Impersonation;

/**
 * Listener to fill impersonation column for audit grids using a separate single query
 */
class AuditGridImpersonationListener
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
    public function addImpersonationSupport(OrmResultAfter $event)
    {
        $ids = [];
        foreach ($event->getRecords() as $record) {
            $id = $record->getValue('impersonation');
            $ids[$id] = $id;
        }

        $ids = array_filter($ids);
        sort($ids);

        /** @var Impersonation[] $impersonations */
        $impersonations = [];
        if ($ids) {
            $impersonations = $this->doctrineHelper->getEntityRepository(Impersonation::class)->findBy(['id' => $ids]);
        }

        $impersonationsByIds = [];
        foreach ($impersonations as $impersonation) {
            $impersonationsByIds[$impersonation->getId()] = $impersonation;
        }

        foreach ($event->getRecords() as $record) {
            $impersonationId = $record->getValue('impersonation');
            $impersonation = $impersonationsByIds[$impersonationId] ?? null;
            $record->setValue('impersonation', $impersonation);
        }
    }
}
