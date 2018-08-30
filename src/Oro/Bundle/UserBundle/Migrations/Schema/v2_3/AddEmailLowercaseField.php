<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

/**
 * This migration adds new field 'emailLowercase' to CustomerUser entity
 * which is required for case insensitive email validation.
 */
class AddEmailLowercaseField implements Migration, OrderedMigrationInterface
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
        if ($table->hasColumn('email_lowercase')) {
            return;
        }

        $table->addColumn('email_lowercase', 'string', ['length' => 255, 'notnull' => false]);
        $table->addIndex(['email_lowercase'], 'idx_oro_user_email_lowercase', []);

        // Fill email_lowercase column with lowercase emails.
        $queries->addPostQuery(new SqlMigrationQuery('UPDATE oro_user SET email_lowercase = LOWER(email)'));
    }
}
