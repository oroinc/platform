<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class AttributeGroupStub extends AttributeGroup
{
    /**
     * @var LocalizedFallbackValue
     */
    private $label;

    /**
     * @param LocalizedFallbackValue $label
     * @return $this
     */
    public function setLabel(LocalizedFallbackValue $label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(Localization $localization = null)
    {
        return $this->label;
    }
}
