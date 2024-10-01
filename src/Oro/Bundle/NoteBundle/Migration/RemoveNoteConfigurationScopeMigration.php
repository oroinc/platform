<?php

namespace Oro\Bundle\NoteBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveNoteConfigurationScopeMigration implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new RemoveNoteConfigurationScopeQuery());
    }
}
