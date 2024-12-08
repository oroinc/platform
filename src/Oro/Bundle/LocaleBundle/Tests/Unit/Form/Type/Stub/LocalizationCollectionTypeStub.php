<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;

class LocalizationCollectionTypeStub extends LocalizationCollectionType
{
    /** @var array */
    protected $localizations;

    public function __construct(array $localizations = [])
    {
        $this->localizations = $localizations;
    }

    #[\Override]
    protected function getLocalizations()
    {
        return $this->localizations;
    }
}
