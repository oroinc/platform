<?php

namespace Oro\Bundle\InstallerBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\NoteBundle\Migration\UpdateNoteAssociationKindForRenamedEntitiesMigration;

class NoteAssociationMigration extends UpdateNoteAssociationKindForRenamedEntitiesMigration
{
    /** @var array [entity class => old entity class, ...] */
    private $renamedEntitiesNames = [];

    /**
     * {@inheritdoc}
     */
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
