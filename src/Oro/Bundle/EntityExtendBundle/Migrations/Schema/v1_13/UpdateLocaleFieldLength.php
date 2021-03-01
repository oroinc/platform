<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updated 'locale' column length to be consistent with the data from 'oro_language' table
 */
class UpdateLocaleFieldLength implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_enum_value_trans');
        $table->changeColumn('locale', ['length' => 16]);
    }
}
