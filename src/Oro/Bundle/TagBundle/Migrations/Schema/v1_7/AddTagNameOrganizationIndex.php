<?php

namespace Oro\Bundle\TagBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddTagNameOrganizationIndex implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_tag_tag');
        $table->addIndex(['name', 'organization_id'], 'name_organization_idx', []);
    }
}
