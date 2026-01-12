<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * API form type for collections of tag entities.
 *
 * This form type provides collection handling for tag entities in API contexts. It configures the form
 * to accept multiple tag entries, allowing dynamic addition of tags and using TagEntityApiType for
 * individual tag entries within the collection.
 */
class TagCollectionApiType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_add'            => true,
                'entry_type'           => TagEntityApiType::class,
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
        return 'oro_tag_collection_api';
    }
}
