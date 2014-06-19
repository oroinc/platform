<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmbeddedFormBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_embedded_form');
        $table->removeForeignKey('FK_F7A34C172F5A1AA');
        $table->dropIndex('IDX_F7A34C172F5A1AA');
        $table->dropColumn('channel_id');
    }
}
