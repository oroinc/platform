<?php

namespace Oro\Bundle\LocaleBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\AbstractParentEntitySearchHandler;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Autocomplete search handler for parent localizations.
 */
class ParentLocalizationSearchHandler extends AbstractParentEntitySearchHandler
{
    #[\Override]
    protected function getChildrenIds($localization)
    {
        return $localization instanceof Localization ? $localization->getChildrenIds() : [];
    }
}
