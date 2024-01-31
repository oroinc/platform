<?php

namespace Oro\Bundle\CurrencyBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigNameQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CurrencyConfigOrganizationMigration implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPreQuery(
            new RenameConfigNameQuery('currency', 'default_currency', 'oro_locale', 'oro_currency')
        );
    }
}
