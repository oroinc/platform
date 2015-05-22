<?php

namespace Oro\Bundle\LDAPBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroLDAPBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

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
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'form'   => ['is_enabled' => false],
            ],
            'notnull' => false
        ]);

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $userTable,
            'ldap_integration_channel',
            'oro_integration_channel',
            'id',
            [
                'oro_options' => [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'form' => ['is_enabled' => false],
                ]
            ]
        );

        $transportTable = $schema->getTable('oro_integration_transport');
        $transportTable->addColumn('oro_ldap_server_hostname', 'string', [
            'notnull' => false
        ]);
        $transportTable->addColumn('oro_ldap_server_port', 'integer', [
            'notnull' => false
        ]);
        $transportTable->addColumn('oro_ldap_server_encryption', 'string', [
            'notnull' => false
        ]);
        $transportTable->addColumn('oro_ldap_server_base_dn', 'string', [
            'notnull' => false
        ]);
        $transportTable->addColumn('oro_ldap_admin_dn', 'string', [
            'notnull' => false
        ]);
        $transportTable->addColumn('oro_ldap_admin_password', 'string', [
            'notnull' => false
        ]);
    }
}
