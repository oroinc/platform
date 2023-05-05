<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_10;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Entity\UserManager;

class UpdateResetAuthStatus implements Migration, ExtendExtensionAwareInterface
{
    private ExtendExtension $extendExtension;

    /**
     * {@inheritDoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $options = new OroOptions();
        $options->set(
            'enum',
            'immutable_codes',
            [
                UserManager::STATUS_ACTIVE,
                UserManager::STATUS_RESET
            ]
        );

        $tableName = $this->extendExtension->getNameGenerator()->generateEnumTableName('auth_status');
        $schema->getTable($tableName)
            ->addOption(OroOptions::KEY, $options);

        $queries->addPostQuery($this->getUpdateQuery($tableName));
    }

    private function getUpdateQuery(string $tableName): ParametrizedSqlMigrationQuery
    {
        $query = new ParametrizedSqlMigrationQuery();

        $query->addSql(
            sprintf(
                'INSERT INTO %s (id, name, priority, is_default) VALUES (:id, :name, :priority, :is_default)',
                $tableName
            ),
            [
                'id' => UserManager::STATUS_RESET,
                'name' => 'Reset',
                'priority' => 2,
                'is_default' => false
            ],
            [
                'id' => Types::STRING,
                'name' => Types::STRING,
                'priority' => Types::INTEGER,
                'is_default' => Types::BOOLEAN
            ]
        );

        $query->addSql(
            'UPDATE oro_user SET auth_status_id = :id WHERE auth_status_id = :old_id',
            ['id' => UserManager::STATUS_RESET, 'old_id' => 'expired'],
            ['id' => Types::STRING, 'old_id' => Types::STRING]
        );

        $query->addSql(
            sprintf('DELETE FROM %s WHERE id = :id', $tableName),
            ['id' => 'expired'],
            ['id' => Types::STRING]
        );

        return $query;
    }
}
