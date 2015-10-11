<?php

namespace Oro\Bundle\ActivityListBundle\Migration;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Psr\Log\LoggerInterface;

use Oro\Bundle\ActivityListBundle\Migration\Extension\ActivityListExtension;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class ActivityListMigrationQuery extends ParametrizedMigrationQuery
{
    /** @var Schema */
    protected $schema;

    /**  @var ActivityListChainProvider */
    protected $provider;

    /** @var ActivityListExtension */
    protected $activityListExtension;

    /** @var EntityMetadataHelper */
    protected $metadataHelper;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param Schema                          $schema
     * @param ActivityListChainProvider       $provider
     * @param ActivityListExtension           $activityListExtension
     * @param EntityMetadataHelper            $metadataHelper
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     * @param ConfigManager                   $configManager
     */
    public function __construct(
        Schema $schema,
        ActivityListChainProvider $provider,
        ActivityListExtension $activityListExtension,
        EntityMetadataHelper $metadataHelper,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        ConfigManager $configManager
    ) {
        $this->schema                = $schema;
        $this->provider              = $provider;
        $this->activityListExtension = $activityListExtension;
        $this->metadataHelper        = $metadataHelper;
        $this->nameGenerator         = $nameGenerator;
        $this->configManager         = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->runActivityLists($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->runActivityLists($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function runActivityLists(LoggerInterface $logger, $dryRun = false)
    {
        // @todo: this workaround should be removed in BAP-9156
        $this->configManager->clear();

        $targetEntities   = $this->provider->getTargetEntityClasses();
        $toSchema         = clone $this->schema;
        $hasSchemaChanges = false;
        foreach ($targetEntities as $targetEntity) {
            $associationName   = ExtendHelper::buildAssociationName(
                $targetEntity,
                ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
            );
            $relationTableName = $this->nameGenerator->generateManyToManyJoinTableName(
                ActivityListEntityConfigDumperExtension::ENTITY_CLASS,
                $associationName,
                $targetEntity
            );
            if (!$toSchema->hasTable($relationTableName)) {
                $hasSchemaChanges = true;
                $this->activityListExtension->addActivityListAssociation(
                    $toSchema,
                    $this->metadataHelper->getTableNameByEntityClass($targetEntity)
                );
            }
        }

        if ($hasSchemaChanges) {
            $comparator = new Comparator();
            $platform   = $this->connection->getDatabasePlatform();
            $schemaDiff = $comparator->compare($this->schema, $toSchema);
            $queries    = $schemaDiff->toSql($platform);
            foreach ($queries as $query) {
                $this->logQuery($logger, $query);
                if (!$dryRun) {
                    $this->connection->executeQuery($query);
                }
            }
        }
    }
}
