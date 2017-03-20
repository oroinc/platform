<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\ORMException;

use Oro\Bundle\WorkflowBundle\Model\Variable;
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
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param VariableGuesser $variableGuesser
     * @param ManagerRegistry $managerRegistry
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
        foreach ($variables as $variable) {
            /** @var TypeGuess $typeGuess */
            $typeGuess = $this->variableGuesser->guessVariableForm($variable);
            if (!$typeGuess instanceof TypeGuess) {
                continue;
            }

            $fieldName = $variable->getName();
            $builder->add($fieldName, $typeGuess->getType(), $typeGuess->getOptions());

            if ('entity' === $variable->getType()) {
                $builder->get($fieldName)
                    ->addModelTransformer(new CallbackTransformer(
                        function ($entity) {
                            return $entity;
                        },
                        function ($entity) use ($variable) {
                            $metadata = $this->getMetadataForClass(get_class($entity));
                            if (!$metadata) {
                                return '';
                            }

                            /** @var Variable $variable */
                            $identifier = $variable->getOption('identifier', null);
                            if (!$identifier) {
                                $identifierFields = $metadata->getIdentifierFieldNames();
                                if (!isset($identifierFields[0])) {
                                    return '';
                                }
                                $identifier = $identifierFields[0];
                            }

                            $accessor = PropertyAccess::createPropertyAccessor();
                            try {
                                return $accessor->getValue($entity, $identifier);
                            } catch (\RuntimeException $e) {
                                return '';
                            }
                        }
                    ));
            }
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
        $resolver
            ->setDefined(['workflow'])
            ->setDefaults([
                'data_class' => 'Oro\Bundle\WorkflowBundle\Model\WorkflowData'
            ])
            ->setAllowedTypes([
                'workflow' => 'Oro\Bundle\WorkflowBundle\Model\Workflow'
            ])
            ->setRequired(['workflow']);
    }

    /**
     * @param null|string $class
     * @return null|ClassMetadata
     */
    protected function getMetadataForClass($class)
    {
        try {
            $entityManager = $this->managerRegistry->getManagerForClass($class);
        } catch (ORMException $e) {
            return null;
        }

        return $entityManager ? $entityManager->getClassMetadata($class) : null;
    }
}
