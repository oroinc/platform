<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Oro\Bundle\SoapBundle\Form\Type\AbstractPatchableApiType;

class TagStandaloneCollectionApiType extends AbstractPatchableApiType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TagCollectionApiType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_tag_standalone_collection_api';
    }
}
