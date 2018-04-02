<?php

namespace Oro\Bundle\InstallerBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * This class can be used to rename the class name of target entities for activity associations
 * during migration to v2.0.
 */
class RenameActivityAssociations20
{
    /** @var Connection */
    private $connection;

    /** @var RenameExtendedManyToManyAssociation20 */
    private $helper;

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
        $this->helper = new RenameExtendedManyToManyAssociation20($connection, $nameGenerator, $renameExtension);
    }

    /**
     * @param Schema   $schema
     * @param QueryBag $queries
     */
    public function rename(Schema $schema, QueryBag $queries)
    {
        $activityClassNames = $this->loadActivityClassNames();
        $isSupportedTarget = function (array $data, $className) {
            return
                !empty($data['activity']['activities'])
                && in_array($className, $data['activity']['activities'], true);
        };
        foreach ($activityClassNames as $activityClassName) {
            $this->helper->rename(
                $schema,
                $queries,
                $activityClassName,
                ActivityScope::ASSOCIATION_KIND,
                $isSupportedTarget
            );
        }
    }

    /**
     * @return string[]
     */
    private function loadActivityClassNames()
    {
        $activityClassNames = [];
        $rows = $this->connection->fetchAll('SELECT class_name, data FROM oro_entity_config');
        foreach ($rows as $row) {
            $className = $row['class_name'];
            $data = $this->connection->convertToPHPValue($row['data'], 'array');
            if (!empty($data['grouping']['groups'])
                && in_array(ActivityScope::GROUP_ACTIVITY, $data['grouping']['groups'], true)
            ) {
                $activityClassNames[] = $className;
            }
        }

        return $activityClassNames;
    }
}
