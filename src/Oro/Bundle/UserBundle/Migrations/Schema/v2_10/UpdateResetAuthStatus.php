<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_10;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Query\OutdatedEnumDataValue;
use Oro\Bundle\EntityExtendBundle\Migration\Query\OutdatedInsertEnumValuesQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Entity\UserManager;

class UpdateResetAuthStatus implements Migration, OutdatedExtendExtensionAwareInterface
{
    use OutdatedExtendExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $options = new OroOptions();
        $options->set('enum', 'immutable_codes', [UserManager::STATUS_ACTIVE, UserManager::STATUS_RESET]);
        $tableName = $this->outdatedExtendExtension::generateEnumTableName('auth_status');
        $schema->getTable($tableName)->addOption(OroOptions::KEY, $options);

        $queries->addPostQuery(new OutdatedInsertEnumValuesQuery($this->outdatedExtendExtension, 'auth_status', [
            new OutdatedEnumDataValue(UserManager::STATUS_RESET, 'Reset', 2)
        ]));
        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_user SET auth_status_id = :id WHERE auth_status_id = :old_id',
            ['id' => UserManager::STATUS_RESET, 'old_id' => 'expired'],
            ['id' => Types::STRING, 'old_id' => Types::STRING]
        ));
        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            sprintf('DELETE FROM %s WHERE id = :id', $tableName),
            ['id' => 'expired'],
            ['id' => Types::STRING]
        ));
    }
}
