<?php

namespace Oro\Bundle\TestFrameworkBundle\Form\Type;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowAwareEntityType extends AbstractType
{
    const NAME = 'oro_test_workflow_aware_entity_type';

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
