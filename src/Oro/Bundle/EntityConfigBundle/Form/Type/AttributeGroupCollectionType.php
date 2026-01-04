<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttributeGroupCollectionType extends AbstractType
{
    public const NAME = 'oro_entity_config_attribute_group_collection';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => AttributeGroupType::class,
                'entry_options' => [
                    'data_class' => AttributeGroup::class,
                ],
                'attr' => [
                    'class' => 'product-price-collection attibute-group-collection'
                ]
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
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
