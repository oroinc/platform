<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updates enum filter values in segment definitions to use the new format with enum code prefix.
 */
class UpdateEnumFilterValues implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new UpdateEnumFilterValuesQuery());
    }
}
