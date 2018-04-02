<?php

namespace Oro\Bundle\ActivityListBundle\Form\Type;

use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType as BaseFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ActivityListFilterType extends AbstractType
{
    const NAME = 'oro_type_activity_list_filter';

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return BaseFilterType::class;
    }

    /**
     * {@inheritdoc}
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
        return static::NAME;
    }
}
