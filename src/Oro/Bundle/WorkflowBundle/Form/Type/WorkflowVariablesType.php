<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowVariablesType extends AbstractType
{
    const NAME = 'oro_workflow_variables';

    /**
     * @var WorkflowRegistry
     */
    protected $workflowRegistry;

    /**
     * @var VariableGuesser
     */
    protected $variableGuesser;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param $variableGuesser $variableGuesser
     */
    public function __construct(WorkflowRegistry $workflowRegistry, VariableGuesser $variableGuesser)
    {
        $this->workflowRegistry = $workflowRegistry;
        $this->variableGuesser = $variableGuesser;
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
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addVariables($builder, $options);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param $options
     */
    protected function addVariables(FormBuilderInterface $builder, $options)
    {
        /** @var Workflow $workflow */
        $workflow = $options['workflow'];
        $variables = $workflow->getVariables(true);
        foreach ($variables as $variable) {
            /** @var TypeGuess $typeGuess */
            $typeGuess = $this->variableGuesser->guessVariableForm($variable);
            $builder->add($variable->getName(), $typeGuess->getType(), $typeGuess->getOptions());
        }
    }

    /**
     * Custom options:
     * - "workflow_definition"      - required, instance of WorkflowDefinition entity
     * - "workflow"                 - optional, instance of Workflow
     *
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['workflow', 'workflow_definition']);

        $resolver->setDefaults(
            [
                'workflow' => function (Options $options, $workflow) {
                    if (!$workflow) {
                        $workflowName = $options['workflow_definition']->getName();
                        $workflow = $this->workflowRegistry->getWorkflow($workflowName);
                    }

                    return $workflow;
                },
                'data_class' => 'Oro\Bundle\WorkflowBundle\Model\WorkflowData',
            ]
        );

        $resolver->setAllowedTypes(
            [
                'workflow_definition' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition',
                'workflow' => 'Oro\Bundle\WorkflowBundle\Model\Workflow',
            ]
        );
    }
}
