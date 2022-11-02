<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

/**
 * This migration adds new field 'usernameLowercase' to User entity
 * which is required for case insensitive username validation.
 */
class AddUsernameLowercaseField implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_user');
        if ($table->hasColumn('username_lowercase')) {
            return;
        }

        $table->addColumn('username_lowercase', 'string', ['length' => 255, 'notnull' => false]);
        $table->addIndex(['username_lowercase'], 'idx_oro_user_username_lowercase', []);

        // Fill username_lowercase column with lowercase usernames.
        $queries->addPostQuery(new SqlMigrationQuery('UPDATE oro_user SET username_lowercase = LOWER(username)'));
    }
}
