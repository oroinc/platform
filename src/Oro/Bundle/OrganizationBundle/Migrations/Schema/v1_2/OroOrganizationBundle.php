<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrganizationBundle implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_organization');

        $table->dropColumn('currency');
        $table->dropColumn('currency_precision');

        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('state', 'boolean', []);

        $queries->addQuery(
            'DELETE FROM oro_entity_config_index_value
             WHERE entity_id IS NULL AND field_id IN(
               SELECT oecf.id FROM oro_entity_config_field AS oecf
               WHERE
                (oecf.field_name = \'precision\' OR oecf.field_name = \'currency\')
                AND
                oecf.entity_id = (
                    SELECT oec.id
                    FROM oro_entity_config AS oec
                    WHERE oec.class_name = \'Oro\\\\Bundle\\\\OrganizationBundle\\\\Entity\\\\Organization\'
                )
             );
             DELETE FROM oro_entity_config_field
               WHERE
                field_name IN (\'precision\', \'currency\')
                AND
                entity_id IN (
                    SELECT id
                    FROM oro_entity_config
                    WHERE class_name = \'Oro\\\\Bundle\\\\OrganizationBundle\\\\Entity\\\\Organization\'
                )'
        );
    }
}
