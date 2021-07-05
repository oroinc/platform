<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\LocaleBundle\Migration\UpdateFallbackExcludedQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Include fallback field to imports
 */
class OroLocaleBundle implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPostQuery(new UpdateFallbackExcludedQuery());
    }
}
