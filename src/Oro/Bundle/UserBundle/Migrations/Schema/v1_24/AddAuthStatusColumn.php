<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_24;

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

class AddAuthStatusColumn implements Migration, OutdatedExtendExtensionAwareInterface
{
    use OutdatedExtendExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $enumTable = $this->outdatedExtendExtension->addOutdatedEnumField(
            $schema,
            'oro_user',
            'auth_status',
            'auth_status'
        );

        $options = new OroOptions();
        $options->set('enum', 'immutable_codes', [UserManager::STATUS_ACTIVE, UserManager::STATUS_RESET]);
        $enumTable->addOption(OroOptions::KEY, $options);

        $queries->addPostQuery(new OutdatedInsertEnumValuesQuery($this->outdatedExtendExtension, 'auth_status', [
            new OutdatedEnumDataValue(UserManager::STATUS_ACTIVE, 'Active', 1, true),
            new OutdatedEnumDataValue(UserManager::STATUS_RESET, 'Reset', 2)
        ]));

        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_user SET auth_status_id = :default_status',
            ['default_status' => UserManager::STATUS_ACTIVE],
            ['default_status' => Types::STRING]
        ));
    }
}
