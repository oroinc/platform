<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Update acl_protected entity field config option for image field of AttributeFamily entity.
 */
class UpdateAttachmentFieldConfigForAttributeFamilyImage implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(AttributeFamily::class, 'image', 'attachment', 'acl_protected', false)
        );
    }
}
