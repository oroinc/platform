<?php

namespace Oro\Bundle\InstallerBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * This class can be used to rename the class name of target entities for extended many-to-many associations
 * during migration to v2.0.
 */
class RenameExtendedManyToManyAssociation20
{
    /** @var Connection */
    private $connection;

    /** @var ExtendDbIdentifierNameGenerator */
    private $nameGenerator;

    /** @var RenameExtension */
    private $renameExtension;

    /**
     * @param Connection                      $connection
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     * @param RenameExtension                 $renameExtension
     */
    public function __construct(
        Connection $connection,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        RenameExtension $renameExtension
    ) {
        $this->connection = $connection;
        $this->nameGenerator = $nameGenerator;
        $this->renameExtension = $renameExtension;
    }

    /**
     * @param Schema   $schema
     * @param QueryBag $queries
     * @param string   $entityClass
     * @param string   $associationKind
     * @param callable $isSupportedTarget
     */
    public function rename(
        Schema $schema,
        QueryBag $queries,
        $entityClass,
        $associationKind,
        $isSupportedTarget
    ) {
        $targetClassNames = $this->loadTargetClassNames($entityClass, $isSupportedTarget);
        foreach ($targetClassNames as $targetClassName) {
            if (0 !== strpos($targetClassName, 'Oro\\')) {
                continue;
            }

            $associationName = ExtendHelper::buildAssociationName($targetClassName, $associationKind);
            $joinTableName = $this->nameGenerator->generateManyToManyJoinTableName(
                $entityClass,
                $associationName,
                $targetClassName
            );

            $oldTargetClassName = 'OroCRM' . substr($targetClassName, 3);
            $oldAssociationName = ExtendHelper::buildAssociationName($oldTargetClassName, $associationKind);
            $oldJoinTableName = $this->nameGenerator->generateManyToManyJoinTableName(
                $entityClass,
                $oldAssociationName,
                $oldTargetClassName
            );

            if ($schema->hasTable($oldJoinTableName) && !$schema->hasTable($joinTableName)) {
                $this->renameExtension->renameTable($schema, $queries, $oldJoinTableName, $joinTableName);
                $queries->addQuery(new UpdateExtendRelationQuery(
                    $entityClass,
                    $targetClassName,
                    $oldAssociationName,
                    $associationName,
                    RelationType::MANY_TO_MANY
                ));
            }
        }
    }

    /**
     * @param string   $entityClass
     * @param callable $isSupportedTarget
     *
     * @return string[]
     */
    private function loadTargetClassNames($entityClass, $isSupportedTarget)
    {
        $targetClassNames = [];
        $rows = $this->connection->fetchAll('SELECT class_name, data FROM oro_entity_config');
        foreach ($rows as $row) {
            $className = $row['class_name'];
            $data = $this->connection->convertToPHPValue($row['data'], 'array');
            if (call_user_func($isSupportedTarget, $data, $entityClass)) {
                $targetClassNames[] = $className;
            }
        }

        return $targetClassNames;
    }
}
