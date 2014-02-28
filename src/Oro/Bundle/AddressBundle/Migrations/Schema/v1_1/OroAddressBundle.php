<?php

namespace Oro\Bundle\AddressBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAddressBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addSql(
            $queries->getRenameTableSql('oro_dictionary_country_translation', 'oro_dictionary_country_trans')
        );
        $queries->addSql(
            $queries->getRenameTableSql('oro_dictionary_region_translation', 'oro_dictionary_region_trans')
        );
    }
}
