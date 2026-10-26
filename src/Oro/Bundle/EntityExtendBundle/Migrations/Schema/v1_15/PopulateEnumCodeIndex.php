<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Makes existing enum field configurations searchable by their enum code.
 * New field configurations are indexed when they are saved; existing ones need a one-time index population.
 */
class PopulateEnumCodeIndex implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new PopulateEnumCodeIndexQuery());
    }
}
