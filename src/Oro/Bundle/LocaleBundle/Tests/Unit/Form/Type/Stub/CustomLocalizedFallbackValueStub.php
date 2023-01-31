<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

class CustomLocalizedFallbackValueStub extends AbstractLocalizedFallbackValue
{
    public function __construct(?int $id = null)
    {
        if ($id !== null) {
            $this->id = $id;
        }
    }
}
