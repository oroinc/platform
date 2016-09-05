<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

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
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'entity_class' => null,
                'config_id'    => null, // can be extracted from parent form
            )
        );

        $resolver->setNormalizers(
            array(
                'choices' => function (Options $options, $value) {
                    if (!empty($value)) {
                        return $value;
                    }

                    $entityClass = $options['entity_class'];
                    if (!$entityClass && $options->has('config_id')) {
                        $configId = $options['config_id'];
                        if ($configId && $configId instanceof ConfigIdInterface) {
                            $entityClass = $configId->getClassName();
                        }
                    }

                    $choices = array();
                    if ($entityClass) {
                        /** @var WorkflowDefinition[] $definitions */
                        $definitions = $this->registry->getRepository('OroWorkflowBundle:WorkflowDefinition')
                            ->findBy(array('relatedEntity' => $entityClass));

                        foreach ($definitions as $definition) {
                            $name = $definition->getName();
                            $label = $definition->getLabel();
                            $choices[$name] = $label;
                        }
                    }

                    return $choices;
                }
            )
        );
    }
}
