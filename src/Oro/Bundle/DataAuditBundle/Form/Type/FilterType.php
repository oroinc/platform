<?php

namespace Oro\Bundle\DataAuditBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType as BaseFilterType;

class FilterType extends AbstractType
{
    const NAME = 'oro_type_audit_filter';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('auditFilter', null, [
                'constraints' => [
                    new Assert\Collection([
                        'fields' => [
                            'data' => [],
                            'type' => [],
                            'columnName' => [],
                        ],
                    ]),
                ],
            ])
            ->add('filter', null, [
                'constraints' => [
                    new Assert\Collection([
                        'fields' => [
                            'data' => [],
                            'type' => [],
                            'filter' => [],
                        ],
                        'allowMissingFields' => true,
                    ]),
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
