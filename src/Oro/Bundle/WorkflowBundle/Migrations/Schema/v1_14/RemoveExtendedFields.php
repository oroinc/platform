<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveExtendedFields implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const PROPERTY_WORKFLOW_ITEM = 'workflowItem';
    const PROPERTY_WORKFLOW_STEP = 'workflowStep';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $entityProvider = $this->container->get('oro_entity.entity_provider');
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $configProvider = $configManager->getProvider('extend');

        foreach ($entityProvider->getEntities() as $entity) {
            $entityName = $entity['name'];
            if ($configProvider->hasConfig($entityName, self::PROPERTY_WORKFLOW_ITEM) &&
                $configProvider->hasConfig($entityName, self::PROPERTY_WORKFLOW_STEP)
            ) {
                $fieldConfigItem = $configProvider->getConfig($entityName, self::PROPERTY_WORKFLOW_ITEM);
                $fieldConfigItem->set('state', ExtendScope::STATE_DELETE);
                $configManager->persist($fieldConfigItem);

                $fieldConfigStep = $configProvider->getConfig($entityName, self::PROPERTY_WORKFLOW_STEP);
                $fieldConfigStep->set('state', ExtendScope::STATE_DELETE);
                $configManager->persist($fieldConfigStep);

                $configManager->flush();
            }
        }

        $configManager->flush();

        $this->container
            ->get('oro_entity_extend.extend.entity_processor')
            ->updateDatabase(true, true);
    }
}
