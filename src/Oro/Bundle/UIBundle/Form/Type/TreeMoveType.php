<?php

namespace Oro\Bundle\UIBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\UIBundle\Model\TreeCollection;
use Oro\Bundle\UIBundle\Form\DataTransformer\TreeItemToStringTransformer;

class TreeMoveType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('source', 'genemu_jqueryselect2_choice', array_merge(
            $options['source_config'],
            [
                'multiple' => true,
            ]
        ));
        $builder->add('target', 'genemu_jqueryselect2_choice', array_merge(
            $options['target_config'],
            [
                'placeholder' => 'Choose target',
                'empty_data' => null,
            ]
        ));

        $builder->get('source')
            ->addModelTransformer(new TreeItemToStringTransformer($options['source_config']['choices']));
        $builder->get('target')
            ->addModelTransformer(new TreeItemToStringTransformer($options['target_config']['choices']));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TreeCollection::class,
            'source_config' => [
                'choices' => []
            ],
            'target_config' => [
                'choices' => []
            ],
        ]);
    }
}
