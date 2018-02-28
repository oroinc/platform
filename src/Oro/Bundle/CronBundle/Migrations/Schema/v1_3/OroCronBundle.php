<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCronBundle implements Migration, DatabasePlatformAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $preSchema = clone $schema;

        $table = $preSchema->getTable('oro_cron_schedule');
        $table->changeColumn('command', ['length' => 255]);
        $table->addColumn('args', 'json_array', ['notnull' => false]);
        $table->addColumn('args_hash', 'string', ['notnull' => false, 'length' => 32]);

        foreach ($this->getSchemaDiff($schema, $preSchema) as $query) {
            $queries->addQuery($query);
        }

        $queries->addQuery(sprintf('UPDATE oro_cron_schedule SET args = \'%s\', args_hash = \'%s\'', '[]', md5('[]')));

        $postSchema = clone $preSchema;

        $table = $postSchema->getTable('oro_cron_schedule');
        $table->changeColumn('args', ['notnull' => true]);
        $table->changeColumn('args_hash', ['notnull' => true, 'length' => 32]);
        $table->dropIndex('UQ_COMMAND');
        $table->addUniqueIndex(['command', 'args_hash', 'definition'], 'UQ_COMMAND');

        foreach ($this->getSchemaDiff($preSchema, $postSchema) as $query) {
            $queries->addQuery($query);
        }
    }

    /**
     * @param Schema $schema
     * @param Schema $toSchema
     * @return array
     */
    protected function getSchemaDiff(Schema $schema, Schema $toSchema)
    {
        $comparator = new Comparator();

        return $comparator->compare($schema, $toSchema)->toSql($this->platform);
    }
}
