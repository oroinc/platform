<?php

declare(strict_types=1);

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v7_0_0_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add audit field columns to support Doctrine's new native JSON type.
 */
class AddJsonAuditFieldColumns implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_audit_field');
        if (!$table->hasColumn('old_json')) {
            $table->addColumn('old_json', 'json', ['notnull' => false]);
        }
        if (!$table->hasColumn('new_json')) {
            $table->addColumn('new_json', 'json', ['notnull' => false]);
        }
    }
}
