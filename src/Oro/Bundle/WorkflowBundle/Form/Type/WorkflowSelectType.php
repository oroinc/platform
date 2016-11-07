<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowSelectType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_workflow_select';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entity_class' => null,
                'config_id' => null, // can be extracted from parent form
            ]
        );

        $resolver->setNormalizer(
            'choices',
            function (Options $options, $value) {
                if (!empty($value)) {
                    return $value;
                }

                $entityClass = $options['entity_class'];
                if (!$entityClass && $options->offsetExists('config_id')) {
                    $configId = $options['config_id'];
                    if ($configId && $configId instanceof ConfigIdInterface) {
                        $entityClass = $configId->getClassName();
                    }
                }

                $choices = [];
                if ($entityClass) {
                    /** @var WorkflowDefinition[] $definitions */
                    $definitions = $this->registry->getRepository(WorkflowDefinition::class)
                        ->findBy(['relatedEntity' => $entityClass]);

                    foreach ($definitions as $definition) {
                        $name = $definition->getName();
                        $label = $definition->getLabel();
                        $choices[$name] = $label;
                    }
                }

                return $choices;
            }
        );
    }
}
