<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider;

class OroWorkflowBundle implements Migration, ContainerAwareInterface, DatabasePlatformAwareInterface
{
    use ContainerAwareTrait;

    const OLD_CONFIG_KEY = 'active_workflow';
    const NEW_CONFIG_KEY = WorkflowSystemConfigManager::CONFIG_KEY;

    const OLD_ITEMS_RELATION = 'workflowItem';
    const OLD_STEPS_RELATION = 'workflowStep';
    const NEW_ITEMS_RELATION = WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME;
    const NEW_STEPS_RELATION = WorkflowVirtualRelationProvider::STEPS_RELATION_NAME;

    /** @var AbstractPlatform */
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
        $this->updateColumns($schema);
        $this->moveActiveWorkflows($queries);
        $this->updateReportsDefinitions($queries);
    }

    /**
     * @param Schema $schema
     */
    public function updateColumns(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_item');
        $table->addColumn('entity_class', 'string', ['notnull' => false]);
        $table->changeColumn('entity_id', ['string', 'length' => 255, 'notnull' => false]);
    }

    /**
     * @param QueryBag $queries
     */
    protected function moveActiveWorkflows(QueryBag $queries)
    {
        /* @var $configManager ConfigManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $configProvider = $configManager->getProvider(WorkflowSystemConfigManager::CONFIG_PROVIDER_NAME);

        foreach ($configProvider->getConfigs() as $config) {
            /* @var $config ConfigInterface */
            if (!$config->has(self::OLD_CONFIG_KEY)) {
                continue;
            }

            $workflow = $config->get(self::OLD_CONFIG_KEY);
            $class = $config->getId()->getClassName();

            $config->set(self::NEW_CONFIG_KEY, [$workflow]);
            $config->remove(self::OLD_CONFIG_KEY);

            $configManager->persist($config);
            $configManager->flush();

            if ($this->platform instanceof MySqlPlatform) {
                $class = str_replace('\\', '\\\\', $class);
            }

            $queries->addPostQuery(sprintf(
                'UPDATE oro_workflow_item SET entity_class = \'%s\' WHERE workflow_name = \'%s\'',
                $class,
                $workflow
            ));
        }
    }

    /**
     * @param QueryBag $queries
     */
    protected function updateReportsDefinitions(QueryBag $queries)
    {
        $queries->addPostQuery(sprintf(
            'UPDATE oro_report SET definition = REPLACE(definition, \'%s\', \'%s\')',
            self::OLD_ITEMS_RELATION, self::NEW_ITEMS_RELATION
        ));
        $queries->addPostQuery(sprintf(
            'UPDATE oro_report SET definition = REPLACE(definition, \'%s\', \'%s\')',
            self::OLD_STEPS_RELATION, self::NEW_STEPS_RELATION
        ));
    }
}
