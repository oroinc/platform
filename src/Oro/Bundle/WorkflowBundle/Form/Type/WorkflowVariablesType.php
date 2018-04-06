<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Form\WorkflowVariableDataTransformer;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowVariablesType extends AbstractType
{
    const NAME = 'oro_workflow_variables';

    /**
     * @var VariableGuesser
     */
    protected $variableGuesser;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param VariableGuesser                 $variableGuesser
     * @param WorkflowVariableDataTransformer $transformer
     */
    public function __construct(VariableGuesser $variableGuesser, ManagerRegistry $managerRegistry)
    {
        $this->variableGuesser = $variableGuesser;
        $this->managerRegistry = $managerRegistry;
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

        /** @var Variable[] $variables */
        foreach ($variables as $variable) {
            /** @var TypeGuess $typeGuess */
            $typeGuess = $this->variableGuesser->guessVariableForm($variable);
            if (!$typeGuess instanceof TypeGuess) {
                continue;
            }

            $fieldName = $variable->getName();
            $guessedType = $typeGuess->getType();
            $transformer = null;

            if ($variable->getType() === 'entity') {
                if (!$guessedType) {
                    $guessedType = EntityType::class;
                }

                $transformer = new WorkflowVariableDataTransformer($this->managerRegistry, $variable);
            }

            $field = $builder->create($fieldName, $guessedType, $typeGuess->getOptions());
            if ($transformer) {
                $field->addModelTransformer($transformer);
            }

            $builder->add($field);
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
            ->setDefault('data_class', 'Oro\Bundle\WorkflowBundle\Model\WorkflowData')
            ->setAllowedTypes('workflow', [Workflow::class])
            ->setRequired(['workflow']);
    }
}
