<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Stub;

use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

class CustomFieldStub extends AbstractLocalizedFallbackValue
{
    private CustomParentStub $parent;

    /**
     * @return CustomParentStub|null
     */
    public function getParent(): ?CustomParentStub
    {
        return $this->parent;
    }

    /**
     * @param CustomParentStub $parent
     */
    public function setParent(CustomParentStub $parent): void
    {
        $this->parent = $parent;
    }
}
