<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Stub;

use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

class CustomFieldStub extends AbstractLocalizedFallbackValue
{
    private CustomParentStub $parent;

    public function getParent(): ?CustomParentStub
    {
        return $this->parent;
    }

    public function setParent(CustomParentStub $parent): void
    {
        $this->parent = $parent;
    }
}
