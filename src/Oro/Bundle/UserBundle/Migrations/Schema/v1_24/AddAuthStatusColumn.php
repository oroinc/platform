<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Query\EnumDataValue;
use Oro\Bundle\EntityExtendBundle\Migration\Query\InsertEnumValuesQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Entity\UserManager;

class AddAuthStatusColumn implements Migration, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $enumTable = $this->extendExtension->addEnumField(
            $schema,
            'oro_user',
            'auth_status',
            'auth_status'
        );

        $options = new OroOptions();
        $options->set('enum', 'immutable_codes', [UserManager::STATUS_ACTIVE, UserManager::STATUS_RESET]);
        $enumTable->addOption(OroOptions::KEY, $options);

        $queries->addPostQuery(new InsertEnumValuesQuery($this->extendExtension, 'auth_status', [
            new EnumDataValue(UserManager::STATUS_ACTIVE, 'Active', 1, true),
            new EnumDataValue(UserManager::STATUS_RESET, 'Reset', 2)
        ]));

        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_user SET auth_status_id = :default_status',
            ['default_status' => UserManager::STATUS_ACTIVE],
            ['default_status' => Types::STRING]
        ));
    }
}
