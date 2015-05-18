<?php

namespace Oro\Bundle\LDAPBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroLDAPBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $userTable = $schema->getTable('oro_user');
        $userTable->addColumn('dn', 'text', [
            'oro_options' => [
                'extend' => ['owner' => ExtendScope::OWNER_SYSTEM]
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
