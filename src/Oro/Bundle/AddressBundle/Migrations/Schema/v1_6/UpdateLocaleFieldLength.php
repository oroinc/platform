<?php

namespace Oro\Bundle\AddressBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updated 'locale' column length to be consistent with the data from 'oro_language' table
 */
class UpdateLocaleFieldLength implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_address_type_translation');
        $table->modifyColumn('locale', ['length' => 16]);

        $table = $schema->getTable('oro_dictionary_country_trans');
        $table->modifyColumn('locale', ['length' => 16]);

        $table = $schema->getTable('oro_dictionary_region_trans');
        $table->modifyColumn('locale', ['length' => 16]);
    }
}
