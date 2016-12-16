<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EntityExtendBundle\Layout\Block\Type\AttributeTextType;

class AttributeTextTypeTest extends AbstractAttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setType()
    {
        return new AttributeTextType($this->getManager());
    }
}
