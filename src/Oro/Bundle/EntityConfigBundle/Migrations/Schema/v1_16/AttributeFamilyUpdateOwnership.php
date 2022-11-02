<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migrations\Schema\SetOwnershipTypeQuery;

/**
 * Update ownership to `ORGANIZATION` for AttributeFamily entity
 */
class AttributeFamilyUpdateOwnership implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_attribute_family');

        if ($table->hasForeignKey('fk_a6a95b469eb185f9')) {
            $table->removeForeignKey('fk_a6a95b469eb185f9');
        }

        if ($table->hasIndex('idx_a6a95b469eb185f9')) {
            $table->dropIndex('idx_a6a95b469eb185f9');
        }

        if ($table->hasColumn('user_owner_id')) {
            $table->dropColumn('user_owner_id');
        }

        $queries->addQuery(
            new SetOwnershipTypeQuery(
                AttributeFamily::class,
                [
                    'owner_type' => 'ORGANIZATION',
                    'owner_field_name' => 'owner',
                    'owner_column_name' => 'organization_id'
                ]
            )
        );
    }
}
