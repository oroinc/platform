<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class UpdateDefinitionTranslations extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $qb = $manager->getRepository(WorkflowDefinition::class)->createQueryBuilder('wd');

        /** @var $definitions WorkflowDefinition[] */
        $definitions = $qb->where($qb->expr()->notIn('wd.name', ':names'))
            ->setParameter('names', $this->getWorkflowNamesFromCurrentConfiguration(), Type::TARRAY)
            ->getQuery()
            ->getResult();

        /** @var $processor TranslationProcessor */
        $processor = $this->container->get('oro_workflow.translation.processor');

        /** @var $handler WorkflowDefinitionHandler */
        $handler = $this->container->get('oro_workflow.handler.workflow_definition');

        foreach ($definitions as $definition) {
            $this->processConfiguration($processor, $definition);
            $handler->updateWorkflowDefinition($definition, $definition);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getWorkflowNamesFromCurrentConfiguration()
    {
        /** @var WorkflowConfigurationProvider $configurationProvider */
        $configurationProvider = $this->container->get('oro_workflow.configuration.provider.workflow_config');
        $workflowConfiguration = $configurationProvider->getWorkflowDefinitionConfiguration();

        return array_keys($workflowConfiguration);
    }

    /**
     * @param TranslationProcessor $processor
     * @param WorkflowDefinition $definition
     */
    protected function processConfiguration(TranslationProcessor $processor, WorkflowDefinition $definition)
    {
        $sourceConfiguration = array_merge(
            $definition->getConfiguration(),
            [
                'name' => $definition->getName(),
                'label' => $definition->getLabel(),
            ]
        );

        $preparedConfiguration = $processor->prepare($definition->getName(), $processor->handle($sourceConfiguration));

        if (isset($preparedConfiguration[WorkflowConfiguration::NODE_STEPS])) {
            $this->setWorkflowdefinitionSteps($definition, $preparedConfiguration[WorkflowConfiguration::NODE_STEPS]);
        }

        $definition->setLabel($preparedConfiguration['label']);

        unset($preparedConfiguration['label'], $preparedConfiguration['name']);

        $definition->setConfiguration($preparedConfiguration);
    }

    /**
     * @param WorkflowDefinition $definition
     * @param array $stepsConfiguration
     */
    protected function setWorkflowdefinitionSteps(WorkflowDefinition $definition, array $stepsConfiguration)
    {
        foreach ($stepsConfiguration as $name => $stepConfiguration) {
            $workflowStep = $definition->getStepByName($name);

            if ($workflowStep && isset($stepConfiguration['label'])) {
                $workflowStep->setLabel($stepConfiguration['label']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            /**
             * Languages should be pre loaded before this fixture will start to work,
             * because the expected behaviour is:
             * If some of the workflow translatable fields values was not translated,
             * value should be saved as the translation of this field
             *
             * @see \Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor::handle
             * @see \Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper::saveTranslation
             * @see \Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper::saveValue
             * @see \Oro\Bundle\TranslationBundle\Manager\TranslationManager::saveTranslation
             * @see \Oro\Bundle\TranslationBundle\Manager\TranslationManager::createTranslation
             */
            LoadLanguageData::class
        ];
    }
}
