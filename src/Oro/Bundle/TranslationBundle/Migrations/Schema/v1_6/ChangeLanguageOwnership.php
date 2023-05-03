<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migrations\Schema\SetOwnershipTypeQuery;

/**
 * Updates ownership for Language entity.
 */
class ChangeLanguageOwnership implements Migration
{
    use MigrationConstraintTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_language');

        $fieldName = 'user_owner_id';
        $fkName = $this->getConstraintName($table, $fieldName);

        $table->removeForeignKey($fkName);
        $table->dropColumn($fieldName);

        // Switch ownership from USER to ORGANIZATION
        $queries->addQuery(
            new SetOwnershipTypeQuery(
                'Oro\Bundle\TranslationBundle\Entity\Language',
                [
                    'owner_type' => 'ORGANIZATION',
                    'owner_field_name' => 'organization',
                    'owner_column_name' => 'organization_id',
                ]
            )
        );
    }
}
