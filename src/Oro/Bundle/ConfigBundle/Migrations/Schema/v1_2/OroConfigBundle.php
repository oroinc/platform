<?php

namespace Oro\Bundle\ConfigBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class OroConfigBundle implements Migration, NameGeneratorAwareInterface
{
    /**
     * @var DbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateConfigForeignKey($schema);
    }

    /**
     * @param Schema $schema
     */
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
