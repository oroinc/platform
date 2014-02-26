<?php

namespace Oro\Bundle\AddressBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Installation;
use Oro\Bundle\AddressBundle\Migrations\Schema\v1_0\OroAddressBundle;

class OroAddressBundleInstaller implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        OroAddressBundle::oroAddressTable($schema);
        OroAddressBundle::oroAddressTypeTable($schema);
        OroAddressBundle::oroAddressTypeTranslationTable($schema);
        OroAddressBundle::oroDictionaryCountryTable($schema);
        OroAddressBundle::oroDictionaryCountryTranslationTable($schema, 'oro_dictionary_country_trans');
        OroAddressBundle::oroDictionaryRegion($schema);
        OroAddressBundle::oroDictionaryRegionTranslationTable($schema, 'oro_dictionary_region_trans');
        OroAddressBundle::addForeignKeys($schema);

        return [];
    }
}
