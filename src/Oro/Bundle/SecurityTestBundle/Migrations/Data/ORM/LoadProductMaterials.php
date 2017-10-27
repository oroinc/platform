<?php

namespace Oro\Bundle\SecurityTestBundle\Migrations\Data\ORM;

class LoadProductMaterials extends AbstractProductAttributeXssFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return 'enum_material';
    }
}
