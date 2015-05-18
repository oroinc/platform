<?php

namespace Oro\Bundle\LDAPBundle\Migrations\Schema\v1_0;

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
        $userTable = $schema->getTable('oro_user');
        $userTable->addColumn('dn', 'text', [
            'oro_options' => [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'form'   => ['is_enabled' => false],
            ],
            'notnull' => false
        ]);

        $transportTable = $schema->getTable('oro_integration_transport');
        $transportTable->addColumn('oro_ldap_server_hostname', 'string');
        $transportTable->addColumn('oro_ldap_server_port', 'integer');
        $transportTable->addColumn('oro_ldap_server_encryption', 'string');
        $transportTable->addColumn('oro_ldap_server_base_dn', 'string');
        $transportTable->addColumn('oro_ldap_admin_dn', 'string', [
            'notnull' => false
        ]);
        $transportTable->addColumn('oro_ldap_admin_password', 'string', [
            'notnull' => false
        ]);
    }
}
