<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Increased the size of fields as there are no restrictions on length of tokens from external services.
 */
class IncreaseTokenLength implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        // Skip this migration. Moved to migration version 1_10_1.
    }
}
