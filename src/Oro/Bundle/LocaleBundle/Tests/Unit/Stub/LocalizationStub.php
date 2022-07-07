<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Stub;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationStub extends Localization
{
    public function __construct(?int $id = null)
    {
        parent::__construct();

        $this->id = $id;
    }
}
