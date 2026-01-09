<?php

namespace Oro\Bundle\NoteBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Migration that removes note configuration scope from the database.
 *
 * This migration handles the removal of note configuration scope data that is no longer
 * needed in the application. It executes a post-migration query to clean up scope-related
 * configuration entries from the database, ensuring that the note configuration remains
 * consistent with the current application requirements.
 */
class RemoveNoteConfigurationScopeMigration implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new RemoveNoteConfigurationScopeQuery());
    }
}
