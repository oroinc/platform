<?php

namespace Oro\Bundle\ThemeBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class ChangeTypeField implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new SqlMigrationQuery(<<<QUERY
            ALTER TABLE oro_theme_configuration ALTER type DROP DEFAULT
        QUERY));

        $queries->addQuery(new SqlMigrationQuery(<<<QUERY
            ALTER TABLE oro_theme_configuration ALTER type DROP NOT NULL
        QUERY));
    }
}
