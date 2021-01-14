<?php

namespace Oro\Bundle\AddressBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updated object_class column length according to
 * {@see \Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation::$objectClass} field metadata change
 */
class UpdateObjectClassFieldLength implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_address_type_translation');
        $table->changeColumn('object_class', ['length' => 191]);

        $table = $schema->getTable('oro_dictionary_country_trans');
        $table->changeColumn('object_class', ['length' => 191]);

        $table = $schema->getTable('oro_dictionary_region_trans');
        $table->changeColumn('object_class', ['length' => 191]);
    }
}
