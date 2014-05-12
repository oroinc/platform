<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

class WorkflowSelectType extends AbstractType
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
                        $workflows = $this->workflowRegistry->getWorkflowsByEntityClass($entityClass);
                        foreach ($workflows as $workflow) {
                            $name = $workflow->getName();
                            $label = $workflow->getLabel();
                            $choices[$name] = $label;
                        }
                    }

                    return $choices;
                }
            )
        );
    }
}
