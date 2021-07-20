<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOAuthTypeMigration implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_email_origin');
        if (!$table->hasColumn('account_type')) {
            $table->addColumn('account_type', 'string', [
                'default' => 'other',
                'notnull' => false,
                'length'  => 255
            ]);

            $this->alterExistingOrigins($queries);
        }
        $this->alterTokenFieldsSize($schema);
    }

    private function alterTokenFieldsSize(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_origin');
        $table->changeColumn('access_token', [
            'length' => 2048
        ]);
        $table->changeColumn('refresh_token', [
            'length' => 2048
        ]);
    }

    private function alterExistingOrigins(QueryBag $queries): void
    {
        $typeGmail = AccountTypeModel::ACCOUNT_TYPE_GMAIL;
        $typeOther = AccountTypeModel::ACCOUNT_TYPE_OTHER;
        $sql = <<<EOSQL
            UPDATE oro_email_origin 
            SET account_type = 
                CASE 
                    WHEN access_token IS NOT NULL OR refresh_token IS NOT NULL THEN '{$typeGmail}'
                    ELSE '{$typeOther}'
                END
            WHERE true; 
EOSQL;

        $queries->addPostQuery($sql);
    }
}
