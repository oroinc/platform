<?php

namespace Oro\Bundle\LocaleBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\AbstractParentEntitySearchHandler;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class ParentLocalizationSearchHandler extends AbstractParentEntitySearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function getChildrenIds($localization)
    {
        return $localization instanceof Localization ? $localization->getChildrenIds() : [];
    }
}
