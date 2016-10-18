<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;

class UpdateDefinitionTranslations extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /* @var $processor TranslationProcessor */
        $processor = $this->container->get('oro_workflow.translation.processor');

        /* @var $definitions WorkflowDefinition[] */
        $definitions = $manager->getRepository(WorkflowDefinition::class)->findBy(['system' => false]);

        foreach ($definitions as $definition) {
            $configuration = $this->process($processor, $definition);

            $definition->setConfiguration($configuration);

            foreach ($definition->getSteps() as $step) {
                $step->setLabel($configuration['steps'][$step->getName()]['label']);
            }
        }

        $manager->flush();
    }

    /**
     * @param TranslationProcessor $processor
     * @param WorkflowDefinition $definition
     * @return array
     */
    protected function process(TranslationProcessor $processor, WorkflowDefinition $definition)
    {
        $sourceConfiguration = array_merge(
            $definition->getConfiguration(),
            [
                'name' => $definition->getName(),
                'label' => $definition->getLabel(),
            ]
        );

        $preparedConfiguration = $processor->prepare($definition->getName(), $processor->handle($sourceConfiguration));

        $definition->setLabel($preparedConfiguration['label']);

        unset($preparedConfiguration['label'], $preparedConfiguration['name']);

        return $preparedConfiguration;
    }
}
