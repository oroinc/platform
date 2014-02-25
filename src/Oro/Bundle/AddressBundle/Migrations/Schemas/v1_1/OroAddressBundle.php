<?php

namespace Oro\Bundle\AddressBundle\Migrations\Schemas\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroAddressBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "ALTER TABLE oro_dictionary_country_translation RENAME TO oro_dictionary_country_trans;",
            "ALTER TABLE oro_dictionary_region_translation RENAME TO oro_dictionary_region_trans;",
        ];
    }
}
