<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_12;

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
        $table = $schema->getTable('oro_enum_value_trans');
        $table->changeColumn('object_class', ['length' => 191]);
    }
}
