<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AssociationChoiceType extends AbstractType
{
    const NAME = 'oro_entity_extend_association_choice';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'empty_value'                  => false,
                'choices'                      => ['No', 'Yes'],
                'tooltip'                      => 'oro.entity_extend.entity.association_choice.tooltip',
                'entity_class'                 => null, // can be full class name or entity name
                'entity_config_scope'          => null,
                'entity_config_attribute_name' => 'enabled'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
