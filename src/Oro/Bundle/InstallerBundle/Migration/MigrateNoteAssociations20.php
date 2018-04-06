<?php

namespace Oro\Bundle\InstallerBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * This class can be used to rename the class name of target entities and
 * convert Note associations from many-to-one to activity association
 * during migration to v2.0.
 */
class MigrateNoteAssociations20
{
    /** @var Connection */
    private $connection;

    /** @var ExtendDbIdentifierNameGenerator */
    private $nameGenerator;

    /** @var ActivityExtension */
    private $activityExtension;

    /** @var ExtendExtension */
    private $extendExtension;

    /**
     * @param Connection                      $connection
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     * @param ActivityExtension               $activityExtension
     * @param ExtendExtension                 $extendExtension
     */
    public function __construct(
        Connection $connection,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        ActivityExtension $activityExtension,
        ExtendExtension $extendExtension
    ) {
        $this->connection = $connection;
        $this->nameGenerator = $nameGenerator;
        $this->activityExtension = $activityExtension;
        $this->extendExtension = $extendExtension;
    }

    /**
     * @param Schema   $schema
     * @param QueryBag $queries
     */
    public function migrate(Schema $schema, QueryBag $queries)
    {
        $migration = new NoteAssociationMigration();
        $migration->setNameGenerator($this->nameGenerator);
        $migration->setActivityExtension($this->activityExtension);
        $migration->setExtendExtension($this->extendExtension);
        $migration->setRenamedEntityNames($this->loadTargetEntities());
        $migration->up($schema, $queries);
    }

    /**
     * @return array [current class name => old class name, ...]
     */
    protected function loadTargetEntities()
    {
        $result = [];
        $noteClassName = 'Oro\Bundle\NoteBundle\Entity\Note';
        $row = $this->connection->fetchAssoc(
            'SELECT data FROM oro_entity_config WHERE class_name = :class LIMIT 1',
            ['class' => $noteClassName],
            ['class' => 'string']
        );
        if ($row) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');
            if (!empty($data['extend']['relation'])) {
                $relationPrefix = sprintf('manyToOne|%s|', $noteClassName);
                $relations = $data['extend']['relation'];
                foreach ($relations as $relationName => $relation) {
                    if (0 !== strpos($relationName, $relationPrefix)
                        || !array_key_exists('owner', $relation)
                        || !$relation['owner']
                        || !array_key_exists('target_entity', $relation)
                    ) {
                        continue;
                    }
                    $targetClass = $relation['target_entity'];
                    $relationPrefixWithTargetClass = sprintf('%s%s|', $relationPrefix, $targetClass);
                    if (0 !== strpos($relationName, $relationPrefixWithTargetClass)) {
                        continue;
                    }
                    $val = explode('|', substr($relationName, strlen($relationPrefixWithTargetClass)));
                    $associationName = reset($val);
                    $expectedAssociationName = ExtendHelper::buildAssociationName($targetClass);
                    if ($associationName === $expectedAssociationName || 0 !== strpos($targetClass, 'Oro\\')) {
                        continue;
                    }
                    $guessedOldTargetClass = 'OroCRM' . substr($targetClass, 3);
                    if ($associationName === ExtendHelper::buildAssociationName($guessedOldTargetClass)) {
                        $result[$targetClass] = $guessedOldTargetClass;
                    }
                }
            }
        }

        return $result;
    }
}
