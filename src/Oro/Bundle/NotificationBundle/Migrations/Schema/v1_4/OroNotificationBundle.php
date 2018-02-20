<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNotificationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addEntityEmailsField($schema);
        $this->dropOwnerColumn($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function dropOwnerColumn(Schema $schema, QueryBag $queries)
    {
        $tableName = 'oro_notification_recip_list';
        $queries->addPreQuery($this->fillAdditionalAssociationsWithOwnerQuery($tableName));
        $table = $schema->getTable($tableName);

        $table->dropColumn('owner');
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    private function fillAdditionalAssociationsWithOwnerQuery($tableName)
    {
        return new ParametrizedSqlMigrationQuery(
            sprintf(
                'UPDATE %s SET additional_email_associations = CASE'
                .' WHEN additional_email_associations IS NULL THEN :owner'
                .' WHEN additional_email_associations != :owner'
                .' AND additional_email_associations NOT LIKE :ownerStart'
                .' AND additional_email_associations NOT LIKE :ownerEnd'
                .' AND additional_email_associations NOT LIKE :ownerMiddle'
                .' THEN CONCAT_WS(\',\', COALESCE(additional_email_associations, \'\'), :owner)'
                .' ELSE additional_email_associations END'
                .' WHERE owner = :isOwner',
                $tableName
            ),
            [
                'isOwner'     => true,
                'owner'       => 'owner',
                'ownerStart'  => 'owner,%',
                'ownerEnd'    => '%,owner',
                'ownerMiddle' => '%,owner,%'
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    private function addEntityEmailsField(Schema $schema)
    {
        $table = $schema->getTable('oro_notification_recip_list');
        $table->addColumn(
            'entity_emails',
            'simple_array',
            [
                'comment' => '(DC2Type:simple_array)',
                'notnull' => false
            ]
        );
    }
}
