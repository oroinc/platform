<?php

declare(strict_types=1);

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v7_1_0_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddImpersonatorUser implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_audit');

        $table->addColumn('impersonator_user_id', 'integer', ['notnull' => false]);
        $table->addIndex(['impersonator_user_id'], 'idx_oro_audit_impersonator_user_id');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['impersonator_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }
}
