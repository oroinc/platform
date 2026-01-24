<?php

namespace Oro\Bundle\ActivityListBundle\Form\Type;

use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType as BaseFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Form type for filtering activity lists by activity type and presence.
 *
 * This form type extends the base {@see FilterType} to provide filtering capabilities for activity lists.
 * It allows users to filter entities based on whether they have or do not have specific activities
 * of a given type. The form includes fields for specifying the entity class, activity field name,
 * activity type, and the filter type (has activity or has not activity). CSRF protection is disabled
 * to allow filtering operations without token validation.
 */
class ActivityListFilterType extends AbstractType
{
    const NAME = 'oro_type_activity_list_filter';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('filter')
            ->add('entityClassName')
            ->add('activityFieldName')
            ->add('activityType', null, [
                'constraints' => [
                    new Assert\Collection([
                        'fields' => [
                            'value' => [],
                        ],
                    ]),
                ],
            ])
            ->add('filterType', ChoiceType::class, [
                'choices' => [
                    ActivityListFilter::TYPE_HAS_ACTIVITY     => ActivityListFilter::TYPE_HAS_ACTIVITY,
                    ActivityListFilter::TYPE_HAS_NOT_ACTIVITY => ActivityListFilter::TYPE_HAS_NOT_ACTIVITY,
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return BaseFilterType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
