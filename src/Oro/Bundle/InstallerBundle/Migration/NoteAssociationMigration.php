<?php

namespace Oro\Bundle\InstallerBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\NoteBundle\Migration\UpdateNoteAssociationKindForRenamedEntitiesMigration;

/**
 * Migration that updates note associations when entities are renamed or relocated.
 *
 * This migration extends the base note association migration to handle entity renames
 * that occur during the installation process. It maintains a mapping of renamed entities
 * (old class name to new class name) and uses this mapping to update all note associations
 * in the database to reference the new entity class names. This ensures that notes remain
 * properly associated with entities even after their class names change.
 */
class NoteAssociationMigration extends UpdateNoteAssociationKindForRenamedEntitiesMigration
{
    /** @var array [entity class => old entity class, ...] */
    private $renamedEntitiesNames = [];

    #[\Override]
    protected function getRenamedEntitiesNames(Schema $schema)
    {
        return $this->renamedEntitiesNames;
    }

    /**
     * @param array $renamedEntitiesNames [entity class => old entity class, ...]
     */
    public function setRenamedEntityNames(array $renamedEntitiesNames)
    {
        $this->renamedEntitiesNames = $renamedEntitiesNames;
    }
}
