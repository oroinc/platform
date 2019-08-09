<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;

class AttributeFamilyStub extends AttributeFamily
{
    use FallbackTrait;

    /**
     * {@inheritdoc}
     */
    public function setImage($image)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getImage()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLabel()
    {
        return $this->getDefaultFallbackValue($this->labels);
    }
}
