<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Returns localization depending on actual implementation
 */
interface LocalizationProviderInterface
{
    /**
     * @return Localization|null
     */
    public function getCurrentLocalization();
}
