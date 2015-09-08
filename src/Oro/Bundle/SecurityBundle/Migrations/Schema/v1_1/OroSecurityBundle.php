<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSecurityBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::updateAclTables($schema, $queries);
    }

    /**
     * Updates acl tables.
     *
     * @param Schema $schema
     * @param QueryBag $queries
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function updateAclTables(Schema $schema, QueryBag $queries)
    {
        // remove platform-depended sql parts, for example "ON UPDATE CASCADE" for MySql
        $aclEntriesTable = $schema->getTable('acl_entries');
        // additional column, which duplicates acl_object_identities.object_identifier field.
        $aclEntriesTable->addColumn(
            'record_id',
            'bigint',
            [
                'unsigned' => true,
                'notnull' => false,
            ]
        );
        $aclEntriesTable->removeForeignKey('FK_46C8B806DF9183C9');
        $aclEntriesTable->addForeignKeyConstraint(
            $schema->getTable('acl_security_identities'),
            ['security_identity_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_46C8B806DF9183C9'
        );


        $aclEntriesTable->removeForeignKey('FK_46C8B8063D9AB4A6');
        $aclEntriesTable->addForeignKeyConstraint(
            $schema->getTable('acl_object_identities'),
            ['object_identity_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_46C8B8063D9AB4A6'
        );

        $aclEntriesTable->removeForeignKey('FK_46C8B806EA000B10');
        $aclEntriesTable->addForeignKeyConstraint(
            $schema->getTable('acl_classes'),
            ['class_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_46C8B806EA000B10'
        );

        $aclObjectIdentityAncestorsTable = $schema->getTable('acl_object_identity_ancestors');
        $aclObjectIdentityAncestorsTable->removeForeignKey('FK_825DE299C671CEA1');
        $aclObjectIdentityAncestorsTable->addForeignKeyConstraint(
            $schema->getTable('acl_object_identities'),
            ['ancestor_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_825DE299C671CEA1'
        );

        $aclObjectIdentityAncestorsTable->removeForeignKey('FK_825DE2993D9AB4A6');
        $aclObjectIdentityAncestorsTable->addForeignKeyConstraint(
            $schema->getTable('acl_object_identities'),
            ['object_identity_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_825DE2993D9AB4A6'
        );

        $aclSecurityIdentityTable = $schema->getTable('acl_security_identities');
        $aclSecurityIdentityTable->addIndex(['username'], 'acl_sids_username_idx');
    }
}
