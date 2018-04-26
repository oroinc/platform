<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicableEntitiesType extends AbstractType
{
    const NAME = 'oro_workflow_applicable_entities';

    /** @var WorkflowEntityConnector */
    protected $entityConnector;

    /**
     * @param WorkflowEntityConnector $entityConnector
     */
    public function __construct(WorkflowEntityConnector $entityConnector)
    {
        $this->entityConnector = $entityConnector;
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
    public function getParent()
    {
        return EntityChoiceType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['apply_exclusions' => false]);

        $resolver->setNormalizer(
            'choices',
            function (Options $options, $choices) {
                foreach ($choices as $item => $class) {
                    if (!$this->entityConnector->isApplicableEntity($class)) {
                        unset($choices[$item]);
                    }
                }

                return $choices;
            }
        );
    }
}
