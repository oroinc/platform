<?php

namespace Oro\Bundle\ActivityListBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType as BaseFilterType;

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
            ->add('activityType', null, [
                'constraints' => [
                    new Assert\Collection([
                        'fields' => [
                            'value' => [],
                        ],
                    ]),
                ],
            ])
            ->add('filterType', 'choice', [
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
        return BaseFilterType::NAME;
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
