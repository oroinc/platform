<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\WorkflowBundle\Field\FieldGenerator;

class ExcludeImportExportFields extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     * @throws \RuntimeException
     */
    public function load(ObjectManager $manager)
    {
        $entityProvider = $this->container->get('oro_entity.entity_provider');
        $entityConnector = $this->container->get('oro_workflow.entity_connector');
        $configProvider = $this->container->get('oro_entity_config.provider.importexport');

        foreach ($entityProvider->getEntities() as $entity) {
            $entityName = $entity['name'];
            if ($entityConnector->isWorkflowAware($entityName)) {
                if ($configProvider->hasConfig($entityName, FieldGenerator::PROPERTY_WORKFLOW_ITEM)) {
                    $fieldConfig = $configProvider->getConfig($entityName, FieldGenerator::PROPERTY_WORKFLOW_ITEM);
                    $fieldConfig->set('excluded', true);
                    $configProvider->persist($fieldConfig);
                }
                if ($configProvider->hasConfig($entityName, FieldGenerator::PROPERTY_WORKFLOW_STEP)) {
                    $fieldConfig = $configProvider->getConfig($entityName, FieldGenerator::PROPERTY_WORKFLOW_STEP);
                    $fieldConfig->set('excluded', true);
                    $configProvider->persist($fieldConfig);
                }
            }
        }

        $configProvider->flush();
    }
}
