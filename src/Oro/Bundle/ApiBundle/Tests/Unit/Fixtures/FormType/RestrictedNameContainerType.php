<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RestrictedNameContainerType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['name_options']['constraints'][] = new Assert\Length(['min' => 5]);
        $options['name_options']['constraints'][] = new Assert\NotBlank();

        $builder
            ->add('name', TextType::class, $options['name_options']);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['name_options' => []]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'test_restricted_name_container';
    }
}
