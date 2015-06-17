<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveOldSchema implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery('UPDATE oro_email_origin eo SET eo.owner_id =
          (SELECT ueo.user_id FROM oro_user_email_origin ueo WHERE ueo.origin_id = eo.id)');
        $queries->addPreQuery('UPDATE oro_email_origin eo SET eo.organization_id =
          (SELECT u.organization_id FROM oro_user u WHERE u.id = eo.owner_id)');

        $schema->dropTable('oro_user_email_origin');
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
