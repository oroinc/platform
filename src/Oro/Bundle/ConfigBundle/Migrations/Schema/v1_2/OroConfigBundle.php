<?php

namespace Oro\Bundle\ConfigBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroConfigBundle implements Migration, NameGeneratorAwareInterface
{
    use NameGeneratorAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateConfigForeignKey($schema);
    }

    private function updateConfigForeignKey(Schema $schema)
    {
        $table = $schema->getTable('oro_config_value');

        $indexName = $this->nameGenerator->generateForeignKeyConstraintName('oro_config_value', ['config_id']);

        $table->removeForeignKey($indexName);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_config'),
            ['config_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
