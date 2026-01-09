<?php

namespace Oro\Bundle\TestFrameworkBundle\Form\Type;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the {@see WorkflowAwareEntity}.
 *
 * This form type provides a simple form for creating and editing workflow-aware test entities,
 * with a name field and proper data class configuration.
 */
class WorkflowAwareEntityType extends AbstractType
{
    public const NAME = 'oro_test_workflow_aware_entity_type';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => WorkflowAwareEntity::class]);
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
}
