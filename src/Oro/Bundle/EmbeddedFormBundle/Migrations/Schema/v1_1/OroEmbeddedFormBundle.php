<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
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
        $table->getColumn('success_message')->setType(Type::getType(Type::TEXT))->setLength(null);
    }
}
