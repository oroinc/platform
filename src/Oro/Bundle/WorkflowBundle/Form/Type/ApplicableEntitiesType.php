<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicableEntitiesType extends AbstractType
{
    public const NAME = 'oro_workflow_applicable_entities';

    /** @var WorkflowEntityConnector */
    protected $entityConnector;

    public function __construct(WorkflowEntityConnector $entityConnector)
    {
        $this->entityConnector = $entityConnector;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EntityChoiceType::class;
    }

    #[\Override]
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
