<?php

namespace Oro\Bundle\AddressBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDeletedColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateCountryDictionaryTable($schema);
        $this->updateRegionDictionaryTable($schema);
    }

    protected function updateCountryDictionaryTable(Schema $schema): void
    {
        $this->updateDictionaryTable($schema, 'oro_dictionary_country');
    }

    protected function updateRegionDictionaryTable(Schema $schema): void
    {
        $this->updateDictionaryTable($schema, 'oro_dictionary_region');
    }

    private function updateDictionaryTable(Schema $schema, string $tableName): void
    {
        $table = $schema->getTable($tableName);
        if (!$table->hasColumn('deleted')) {
            $table->addColumn('deleted', 'boolean', ['default' => false]);
        }
    }
}
