<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;

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
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return EntityChoiceType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer(
            'choices',
            function (Options $options, $choices) {
                foreach ($choices as $class => $item) {
                    if (!$this->entityConnector->isApplicableEntity($class)) {
                        unset($choices[$class]);
                    }
                }

                return $choices;
            }
        );
    }
}
