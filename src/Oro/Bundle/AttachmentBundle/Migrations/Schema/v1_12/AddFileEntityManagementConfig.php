<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityManagementConfig;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds entity config section entity_management with option enabled => false
 */
class AddFileEntityManagementConfig implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new UpdateEntityConfigEntityValueQuery(
            File::class,
            EntityManagementConfig::SECTION,
            EntityManagementConfig::OPTION,
            false,
            false,
        ));
    }
}
