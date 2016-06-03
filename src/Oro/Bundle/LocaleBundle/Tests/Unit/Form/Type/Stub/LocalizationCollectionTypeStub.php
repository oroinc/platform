<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;

class LocalizationCollectionTypeStub extends LocalizationCollectionType
{
    /** {@inheritdoc} */
    public function __construct()
    {
    }

    /** {@inheritdoc} */
    protected function getLocalizations()
    {
        return [];
    }
}
