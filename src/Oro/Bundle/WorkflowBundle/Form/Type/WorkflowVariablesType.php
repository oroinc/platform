<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class WorkflowVariablesType extends AbstractType
{
    const NAME = 'oro_workflow_variables';

    /**
     * @var VariableGuesser
     */
    protected $variableGuesser;

    /**
     * @param VariableGuesser $variableGuesser
     */
    public function __construct(VariableGuesser $variableGuesser)
    {
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
     * @param array $options
     */
    protected function addVariables(FormBuilderInterface $builder, array $options)
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
     * - "workflow" - required, instance of Workflow
     *
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['workflow'])
            ->setDefaults(['data_class' => 'Oro\Bundle\WorkflowBundle\Model\WorkflowData'])
            ->setAllowedTypes(['workflow' => 'Oro\Bundle\WorkflowBundle\Model\Workflow'])
            ->setRequired(['workflow']);
    }
}
