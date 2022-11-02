<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Update uniqueness by the field "code" to uniqueness by code and organization for oro_attribute_family
 */
class AttributeFamilyUpdateIndexes implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_attribute_family');
        $indexName = 'uniq_oro_attribute_family_code';

        if ($table->hasIndex($indexName)) {
            $table->dropIndex($indexName);
        }

        $table->addUniqueIndex(['code', 'organization_id'], 'oro_attribute_family_code_org_uidx');
    }
}
