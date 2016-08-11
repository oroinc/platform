<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType as BaseFilterType;

class EntityTagsFilterType extends AbstractType
{
    const NAME = 'oro_type_tag_filter';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('filter')
            ->add(
                'value',
                'oro_tag_entity_tags_selector',
                [
                    'entity_class'         => $options['entity_class'],
                    'multiple'             => true,
                    'translatable_options' => true
                ]
            );
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'entity_class'     => null,
                'populate_default' => false,
                'csrf_protection'  => false
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return BaseFilterType::NAME;
    }
}
