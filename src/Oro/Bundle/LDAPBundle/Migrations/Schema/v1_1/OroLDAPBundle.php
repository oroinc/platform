<?php

namespace Oro\Bundle\LDAPBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroLDAPBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $transportTable = $schema->getTable('oro_integration_transport');
        $transportTable->addColumn('server_hostname', 'string', [
        ]);
        $transportTable->addColumn('server_port', 'integer', [
        ]);
        $transportTable->addColumn('server_encryption', 'string', [
        ]);
        $transportTable->addColumn('server_base_dn', 'string', [
        ]);
        $transportTable->addColumn('admin_dn', 'string', [
            'notnull' => false
        ]);
        $transportTable->addColumn('admin_password', 'string', [
            'notnull' => false
        ]);
    }
}
