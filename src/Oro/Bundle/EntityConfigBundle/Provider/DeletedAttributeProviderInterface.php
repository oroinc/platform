<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

interface DeletedAttributeProviderInterface extends AttributeValueProviderInterface
{
    /**
     * @param array $ids
     * @return FieldConfigModel[]
     */
    public function getAttributesByIds(array $ids);
}
