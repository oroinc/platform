<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\LocaleBundle\Form\Type\LocaleCollectionType;

class LocaleCollectionTypeStub extends LocaleCollectionType
{
    /** {@inheritdoc} */
    public function __construct()
    {
    }

    /** {@inheritdoc} */
    protected function getLocales()
    {
        return [];
    }
}
