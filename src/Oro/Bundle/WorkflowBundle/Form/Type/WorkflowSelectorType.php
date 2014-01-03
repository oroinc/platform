<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

class WorkflowSelectorType extends AbstractType
{
    /**
     * @var WorkflowRegistry
     */
    protected $workflowRegistry;

    public function __construct(WorkflowRegistry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_workflow_selector';
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
        $resolver->setDefaults(array('entity_class' => null));

        $resolver->setNormalizers(
            array(
                'choices' => function (Options $options, $value) {
                    if (!empty($value)) {
                        return $value;
                    }

                    $entityClass = $options['entity_class'];
                    $configId = $options['config_id'];
                    if (!$entityClass && $configId && $configId instanceof ConfigIdInterface) {
                        $entityClass = $configId->getClassName();
                    }

                    $choices = array();
                    if ($entityClass) {
                        $entityWorkflows = $this->workflowRegistry->getWorkflowsByEntityClass($entityClass);
                        foreach ($entityWorkflows as $workflow) {
                            $choices[$workflow->getName()] = $workflow->getLabel();
                        }
                    }

                    return $choices;
                }
            )
        );
    }
}
