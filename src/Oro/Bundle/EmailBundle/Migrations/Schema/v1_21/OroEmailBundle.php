<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_21;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_email_recipient')
            ->addIndex(['email_id', 'type'], 'email_id_type_idx');
        $schema->getTable('oro_email_origin')
            ->addIndex(['isActive', 'name'], 'isActive_name_idx');
        $schema->getTable('oro_email')
            ->addIndex(['sent'], 'IDX_sent');
    }
}
