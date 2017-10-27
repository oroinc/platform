<?php

namespace Oro\Bundle\SecurityTestBundle\Migrations\Data\ORM;

class LoadProductColors extends AbstractProductAttributeXssFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return 'enum_color';
    }
}
