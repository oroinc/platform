<?php

namespace Oro\Bundle\SecurityTestBundle\Migrations\Data\ORM;

class LoadProductSizes extends AbstractProductAttributeXssFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return 'enum_size';
    }
}
