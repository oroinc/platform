<?php

namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;

/**
 * Provides the interface for converters that converts field changes to the list of AbstractAuditField entities.
 * These converters are a part of EntityChangesToAuditEntryConverter.
 * @see \Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter
 */
interface ChangeSetToAuditFieldsConverterInterface
{
    /**
     * Converts the given change set to the list of AbstractAuditField objects.
     *
     * @param string        $auditEntryClass The FQCN of an entity used to store audit history for an entity
     * @param string        $auditFieldClass The FQCN of an entity used to store audit history for a field
     * @param ClassMetadata $entityMetadata  The ORM metadata of the audited entity
     * @param array         $changeSet       The changed data
     *
     * @return AbstractAuditField[]
     */
    public function convert(
        string $auditEntryClass,
        string $auditFieldClass,
        ClassMetadata $entityMetadata,
        array $changeSet
    ): array;
}
