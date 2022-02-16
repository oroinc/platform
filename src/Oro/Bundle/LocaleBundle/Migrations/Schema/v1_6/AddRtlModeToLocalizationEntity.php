<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds new field called "rtl_mode" to the Localization entity.
 */
class AddRtlModeToLocalizationEntity implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_localization');

        if ($table->hasColumn('rtl_mode')) {
            return;
        }

        $table->addColumn('rtl_mode', 'boolean', ['default' => false]);
    }
}
