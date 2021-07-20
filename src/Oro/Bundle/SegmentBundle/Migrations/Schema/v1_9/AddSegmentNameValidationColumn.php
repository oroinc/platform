<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddSegmentNameValidationColumn implements Migration, DatabasePlatformAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * Sets the database platform
     */
    public function setDatabasePlatform(AbstractPlatform $platform): void
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schemaWithNewColumn = clone $schema;

        $queries->addQuery("
          UPDATE oro_segment 
          SET name = CONCAT(name, ' ', id) 
          WHERE id IN (
            SELECT * FROM (
              SELECT s1.id
              FROM oro_segment s1 
              JOIN oro_segment s2 
              ON LOWER(s1.name) = LOWER(s2.name) AND s1.id != s2.id
            ) AS duplicateIds
          )");

        $schemaWithNewColumn
            ->getTable('oro_segment')
            ->addColumn(
                'name_lowercase',
                'string',
                ['length' => 255, 'notnull' => false]
            );

        foreach ($this->getSchemaDiff($schema, $schemaWithNewColumn) as $query) {
            $queries->addQuery($query);
        }

        $queries->addQuery('UPDATE oro_segment SET name_lowercase = LOWER(name)');

        $schemaWithModifiedColumn = clone $schemaWithNewColumn;
        $schemaWithModifiedColumn
            ->getTable('oro_segment')
            ->changeColumn('name_lowercase', ['notnull' => true])
            ->addUniqueIndex(['name_lowercase'], 'uniq_d02603b37edd63ff')
            ->dropIndex('uniq_d02603b35e237e06');

        foreach ($this->getSchemaDiff($schemaWithNewColumn, $schemaWithModifiedColumn) as $query) {
            $queries->addQuery($query);
        }
    }

    protected function getSchemaDiff(Schema $schema, Schema $toSchema): array
    {
        return (new Comparator())->compare($schema, $toSchema)->toSql($this->platform);
    }
}
