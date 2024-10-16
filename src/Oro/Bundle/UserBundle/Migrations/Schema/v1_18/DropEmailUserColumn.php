<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropEmailUserColumn implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 4;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_email_user');
        $table->dropColumn('folder_id');

        $table = $schema->getTable('oro_email_user_folders');
        $table->dropIndex('IDX_origin');
        $table->dropColumn('origin_id');
        $table->dropIndex('IDX_email');
        $table->dropColumn('email_id');
    }
}
