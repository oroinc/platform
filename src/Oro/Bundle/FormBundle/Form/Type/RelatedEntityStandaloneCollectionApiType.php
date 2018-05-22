<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\SoapBundle\Form\Type\AbstractPatchableApiType;

class RelatedEntityStandaloneCollectionApiType extends AbstractPatchableApiType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return RelatedEntityCollectionApiType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_related_entity_standalone_collection_api';
    }
}
